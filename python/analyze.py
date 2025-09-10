# python/analyze.py

import sys, json, warnings
from pathlib import Path
warnings.filterwarnings("ignore")  # Suppress warnings for cleaner output

def jerr(msg): 
    # Helper to return error JSON
    return {"ok": False, "error": msg}

def main():
    try:
        import pandas as pd
        from collections import Counter

        # Check for required file path argument
        if len(sys.argv) < 2:
            return jerr("Missing file path arg")

        excel_path = Path(sys.argv[1])
        if not excel_path.exists():
            return jerr(f"File not found: {excel_path}")

        # Pick engine by extension for Excel reading
        ext = excel_path.suffix.lower()
        read_kwargs = {"dtype": str}
        if ext == ".xls":
            # Needs xlrd==1.2.0 (newer xlrd removed .xls support)
            read_kwargs["engine"] = "xlrd"

        try:
            # Read Excel file into DataFrame
            df = pd.read_excel(excel_path, **read_kwargs)
        except Exception as e:
            return jerr(f"ReadError: {type(e).__name__}: {e}")

        # Expected headers in the Excel file
        # AccNo | Date | MainDesc | AddDesc | TransactionType | Amount | Balance | SpendCategory | Currency
        COL_DATE   = "date"
        COL_MAIN   = "maindesc"
        COL_ADD    = "adddesc"
        COL_AMOUNT = "amount"
        COL_SPEND  = "spendcategory"
        COL_CURR   = "currency"

        # Normalize column names to lowercase and strip spaces
        df.columns = [str(c).strip().lower() for c in df.columns]
        for need in (COL_MAIN, COL_AMOUNT):
            if need not in df.columns:
                return jerr(f"Missing column '{need}'")

        def S(x):
            # Helper to safely convert values to string
            return "" if x is None or (isinstance(x, float) and pd.isna(x)) else str(x).strip()

        # Combine main and additional descriptions if available
        if COL_ADD in df.columns:
            df["description"] = (df[COL_MAIN].map(S) + " " + df[COL_ADD].map(S)).str.replace(r"\s+"," ",regex=True).str.strip()
        else:
            df["description"] = df[COL_MAIN].map(S)

        # Clean and convert amount column to numeric
        amt = df[COL_AMOUNT].fillna("0").astype(str).str.replace(",", "", regex=False).str.replace(" ", "", regex=False)
        df["amount"] = pd.to_numeric(amt, errors="coerce").fillna(0.0)

        # Optional columns: date and currency
        df["date"]     = df[COL_DATE].map(S)  if COL_DATE in df.columns else ""
        df["currency"] = df[COL_CURR].map(S)  if COL_CURR in df.columns else ""

        # Rules for auto-categorizing transactions
        RULES = {
            "Wages": ["salary","payroll","wage","stipend"],
            "Business Costs": ["office","software","subscription","aws","gcp","azure","hosting","domain","license","tool","service","internet","phone","rent"],
            "Expense": ["travel","uber","flight","hotel","meal","lunch","dinner","fuel","petrol","taxi","maintenance","repairs","parking","food","fast food","tesco","farmfoods","catering","restaurant","grill","hot"],
        }
        def classify(desc, amount):
            # Classify transaction based on description and amount
            d = desc.lower()
            for cat, keys in RULES.items():
                if any(k in d for k in keys): return cat
            return "Expense" if amount < 0 else "Business Costs"

        # Use provided spend category if available, else auto-classify
        if COL_SPEND in df.columns:
            provided = df[COL_SPEND].astype(str).fillna("").str.strip()
            auto = [classify(d, a) for d, a in zip(df["description"], df["amount"])]
            df["category"] = [p if p and p.lower() not in ("nan","none") else a for p,a in zip(provided, auto)]
        else:
            df["category"] = [classify(d, a) for d, a in zip(df["description"], df["amount"])]

        from collections import Counter
        # Count frequency of each unique description (case-insensitive)
        name_counts = Counter(df["description"].str.lower().str.replace(r"\s+"," ",regex=True).str.strip())
        # Sum amounts by category
        by_cat = df.groupby("category", dropna=False)["amount"].sum().reset_index().sort_values("amount")

        # Prepare output rows and summary
        rows = df[["description","amount","category","date","currency"]].to_dict(orient="records")
        cat_totals = {str(r["category"]): float(r["amount"]) for _, r in by_cat.iterrows()}
        top_names = [{"name": n, "count": int(c)} for n,c in name_counts.most_common(20)]

        return {"ok": True, "summary": {"category_totals": cat_totals, "top_similar": top_names}, "rows": rows}

    except Exception as e:
        # Catch-all error handler
        return jerr(f"{type(e).__name__}: {e}")

if __name__ == "__main__":
    # Output result as JSON to stdout
    sys.stdout.write(json.dumps(main(), ensure_ascii=False))
