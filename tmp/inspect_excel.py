import pandas as pd
import sys

file_path = r'c:\xampp\htdocs\etz-downtime-tracker\migration\2026-03-26T14_08_18.3898181Z.xlsx'

try:
    df = pd.read_excel(file_path)
    print("--- HEADERS ---")
    print(df.columns.tolist())
    print("\n--- FIRST 5 ROWS ---")
    print(df.head().to_string())
except Exception as e:
    print(f"Error reading Excel: {e}")
    # Fallback to openpyxl if pandas fails or is not found
    try:
        import openpyxl
        wb = openpyxl.load_workbook(file_path, read_only=True)
        sheet = wb.active
        headers = [cell.value for cell in sheet[1]]
        print("--- HEADERS (openpyxl) ---")
        print(headers)
        print("\n--- FIRST 5 ROWS (openpyxl) ---")
        for row in sheet.iter_rows(min_row=2, max_row=6, values_only=True):
            print(row)
    except Exception as e2:
        print(f"Error with openpyxl: {e2}")
