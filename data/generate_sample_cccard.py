"""
Generate sample Excel file for CC Card testing
"""
import pandas as pd
from datetime import datetime

# Sample data with CC Card structure
data = {
    'No.': [1, 2, 3],
    'Booking ID': ['1270119439', '1270146017', '1270116243'],
    'Name': ['MUHAMMAD SUSGANDINATA', 'KIKI RESKI ANDRIANI', 'ERIK WICAKSONO'],
    'Personel Number': ['86097601', '93153811', '90087609'],
    'Trip Number': ['4120176655', '4120176640', '4120176608'],
    'Trip Destination': ['Kota Makassar - Kota Yogyakarta', 'Kota Makassar - Kota Semarang', 'Kota Kendari - Kota Makassar'],
    'Trip Date': ['16/07/2025 - 19/07/2025', '15/07/2025 - 19/07/2025', '16/07/2025 - 18/07/2025'],
    'Payment': [413690, 865046, 925400],
    'Transaction Type': ['payment', 'payment', 'payment']
}

df = pd.DataFrame(data)

# Create Excel file
with pd.ExcelWriter('sample_cccard_test.xlsx', engine='openpyxl') as writer:
    df.to_excel(writer, sheet_name='Sheet1', index=False)

print("✓ Sample CC Card Excel created: sample_cccard_test.xlsx")
print("\nFile structure:")
print(f"  - Rows: {len(df)} transactions")
print(f"  - Columns: {len(df.columns)}")
print("\nColumn structure:")
for col in df.columns:
    print(f"  - {col}")
print("\nYou can now upload this file through CC Card Import modal to test the auto-convert feature.")
print("\nNote: Sheet name will be extracted from filename (sample_cccard_test)")
