# Data Analyzer

Analyze your Excel transaction data in seconds!</b><br>
Upload, categorize, visualize through variation of charts, and export your financial data with ease.

---

## 🚀 What is Data Analyzer?

**Data Analyzer** is a web application that lets you upload Excel files containing transaction data, then automatically analyzes, categorizes, and visualizes your spending. It provides instant summaries, interactive charts, and lets you export your results as a PDF.

---

## 🎯 Purpose

- **Automate** the process of categorizing and summarizing financial transactions from Excel files.
- **Visualize** your spending by category with interactive charts.
- **Export** your analysis as a professional PDF report.
- **No manual data entry** — just upload and get insights!

---

## 🧐 Why use this project?

- **Save time:** No more manual sorting or Excel formulas.
- **Instant insights:** See where your money goes, spot trends, and get summaries.
- **Easy to use:** Simple upload form, clear results, and export options.
- **Customizable:** Built with Laravel and Python, so you can extend or adapt it for your needs.

---

## 📦 Features

- Upload `.xlsx` or `.xls` files with transaction data.
- Automatic column normalization and error handling.
- Smart categorization of transactions (Wages, Business Costs, Expense, etc.).
- Interactive charts (bar, pie, line, and more) using Chart.js.
- Paginated transaction tables for easy browsing.
- Export results as a clean, printable PDF.
- Works locally — your data stays private.

---

## 📝 Expected Excel Format

Your Excel file should have at least these columns:

- **MainDesc** (main description)
- **AddDesc** (additional description)
- **Amount**

Optional columns:

- **Date**
- **SpendCategory**
- **Currency**

---

## ⚡️ Quick Start

### 1. Clone the repository

```bash
git clone https://github.com/yourusername/Data_Analyzer.git
cd Data_Analyzer
```

### 2. Install PHP & Composer dependencies

```bash
composer install
```

### 3. Install Python dependencies

Make sure you have Python 3.9+ and pip installed.

```bash
pip install -r requirements.txt
```

> **Note:** If you use a virtual environment, activate it first.

### 4. Set up your environment

Copy `.env.example` to `.env` and set your app key:

```bash
cp .env.example .env
php artisan key:generate
```

### 5. Run the application

```bash
php artisan serve
```

Visit [http://localhost:8000](http://localhost:8000) in your browser.

---

## 🖼️ Screenshots

<p align="center">
  <img src="https://user-images.githubusercontent.com/placeholder/form.png" width="400" alt="Upload Form">
  <img src="https://user-images.githubusercontent.com/placeholder/results.png" width="400" alt="Results Page">
</p>

---

## 🛠️ How it works

1. **Upload:** You upload your Excel file via the web form.
2. **Analyze:** The backend (Python script) reads and processes your file, categorizes transactions, and summarizes totals.
3. **Visualize:** Results are shown with interactive charts and tables.
4. **Export:** You can export the analysis as a PDF report.

---

## 🧩 Tech Stack

- **Backend:** Laravel (PHP)
- **Data Processing:** Python (pandas)
- **Frontend:** Blade templates, Chart.js
- **PDF Export:** DomPDF

---

## 🐍 Python Requirements

- `pandas`
- `openpyxl`
- `xlrd==1.2.0` (for `.xls` support)

Install with:

```bash
pip install pandas openpyxl xlrd==1.2.0
```

---

## 🤝 Contributing

Pull requests and suggestions are welcome! Please open an issue or submit a PR.

---

## 📄 License

This project is open-sourced under the [MIT license](LICENSE).

---

## 💡 Credits

- Built with [Laravel](https://laravel.com/) and [pandas](https://pandas.pydata.org/)
- Charting by [Chart.js](https://www.chartjs.org/)
- PDF export via [DomPDF](https://github.com/dompdf/dompdf)

---

<p align="center">
  <b>Made with ❤️ for data enthusiasts</b>
