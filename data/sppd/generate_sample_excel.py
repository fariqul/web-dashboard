"""
Generate sample Excel file for SPPD testing
"""
import pandas as pd
from datetime import datetime, timedelta

# Sample data with correct structure
data = {
    'Trip Number': [1001234567, 1001234568, 1001234569],
    'Customer Name': ['John Doe', 'Jane Smith', 'Bob Wilson'],
    'Trip Destination': ['Jakarta', 'Bandung', 'Surabaya'],
    'Reason for Trip': ['Site Survey', 'Meeting', 'Training'],
    'Trip Begins On': [datetime(2025, 11, 1), datetime(2025, 11, 5), datetime(2025, 11, 10)],
    'Trip Ends On': [datetime(2025, 11, 3), datetime(2025, 11, 6), datetime(2025, 11, 12)],
    'Extra Col 1': ['', '', ''],  # Dummy columns
    'Extra Col 2': ['', '', ''],
    'Extra Col 3': ['', '', ''],
    'Extra Col 4': ['', '', ''],
    'Paid Amount': [5000000, 3500000, 7200000],
    'Beneficiary Bank Name': ['BCA', 'Mandiri', 'BNI'],
    'Extra Col 5': ['', '', ''],
    'Extra Col 6': ['', '', ''],
}

df = pd.DataFrame(data)

# Create Excel file with proper date formatting
with pd.ExcelWriter('sample_sppd_test.xlsx', engine='openpyxl') as writer:
    df.to_excel(writer, sheet_name='Sheet1 (2)', index=False)

print("✓ Sample Excel created: sample_sppd_test.xlsx")
print("\nFile structure:")
print(f"  - Sheet name: Sheet1 (2)")
print(f"  - Rows: {len(df) + 1} (including header)")
print(f"  - Columns: {len(df.columns)}")
print("\nYou can now upload this file through SPPD Import modal to test the auto-convert feature.")
