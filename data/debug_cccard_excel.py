"""
Debug CC Card Excel conversion
"""
import pandas as pd

# Read the sample Excel
try:
    df = pd.read_excel('sample_cccard_test.xlsx')
    print("✓ Excel file read successfully\n")
    print("Columns found:")
    for col in df.columns:
        print(f"  - {col}")
    
    print(f"\nTotal rows: {len(df)}")
    print("\nFirst 3 rows:")
    print(df.head(3).to_string())
    
except Exception as e:
    print(f"✗ Error reading Excel: {e}")
