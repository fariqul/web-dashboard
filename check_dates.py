import pandas as pd

excel_file = r'D:\Bu Intan\Lampiran Ams - Tgl Bayar 17112025.XLSX'

# Check Sheet1
df1 = pd.read_excel(excel_file, sheet_name='Sheet1', header=0)
print('=== SHEET1 ===')
print('Trip Begins On column:')
for i in range(5):
    val = df1['Trip Begins On'].iloc[i]
    print(f'  Row {i}: {val} (type: {type(val).__name__})')

print('\n=== SHEET1 (2) ===')
df2 = pd.read_excel(excel_file, sheet_name='Sheet1 (2)', header=0)
print('Trip Begins On column:')
for i in range(5):
    val = df2['Trip Begins On'].iloc[i]
    print(f'  Row {i}: {val} (type: {type(val).__name__})')
