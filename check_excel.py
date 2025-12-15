import pandas as pd

# Check Excel structure
xl = pd.ExcelFile(r"D:\Bu Intan\Lampiran Ams - Tgl Bayar 17112025.XLSX")

# Check both Sheet1 sheets
for sheet_name in ['Sheet1', 'Sheet1 (2)']:
    print(f"\n{'='*60}")
    print(f"Checking {sheet_name} structure:")
    print('='*60)
    df = xl.parse(sheet_name, header=None)
    print(f"Total rows: {len(df)}")
    print(f"\nFirst 15 rows:")
    for i in range(min(15, len(df))):
        row_data = df.iloc[i].values
        # Show first 8 columns
        print(f"Row {i}: {row_data[:8]}")

