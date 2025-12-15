import pandas as pd

df = pd.read_excel(r'D:\Bu Intan\Lampiran Ams - Tgl Bayar 17112025.XLSX', sheet_name='Sheet1', header=0)
print('All columns in Sheet1:')
for i, col in enumerate(df.columns):
    print(f'{i}: {col}')
