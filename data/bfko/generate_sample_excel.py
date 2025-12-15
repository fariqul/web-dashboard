"""
Generate sample Excel file for BFKO testing
"""
import pandas as pd
from datetime import datetime

# Sample data with BFKO structure
data = {
    'No': [1, 2, 3],
    'NIP': ['7195001K', '7195002K', '7195003K'],
    'Nama Pegawai': ['DARMADI', 'BUDI SANTOSO', 'SRI WAHYUNI'],
    'Jabatan': ['SRM NIAGA DAN MP', 'MANAGER', 'SUPERVISOR'],
    'Jenjang Jabatan': ['MANAJEMEN ATAS', 'MANAJEMEN MENENGAH', 'PROFESIONAL'],
    'Unit': ['UID SULSELRABAR', 'UID JAKARTA', 'UID BANDUNG'],
    'Sisa Angsuran': [0, 0, 0],
    'Januari': [3734355, 2500000, 1800000],
    'tgl': [datetime(2025, 1, 15), datetime(2025, 1, 20), datetime(2025, 1, 25)],
    'Februari': [3734355, 2500000, 1800000],
    'tgl.1': [datetime(2025, 2, 15), datetime(2025, 2, 20), datetime(2025, 2, 25)],
    'Maret': [0, 0, 0],
    'tgl.2': ['', '', ''],
}

df = pd.DataFrame(data)

# Create Excel with proper formatting
with pd.ExcelWriter('sample_bfko_test.xlsx', engine='openpyxl') as writer:
    # Write title row
    title_df = pd.DataFrame([['Realisasi Pembayaran Angsuran BFKO Periode Januari - Desember 2024']])
    title_df.to_excel(writer, sheet_name='Sheet1', index=False, header=False, startrow=0)
    
    # Write data with header
    df.to_excel(writer, sheet_name='Sheet1', index=False, startrow=2)

print("✓ Sample BFKO Excel created: sample_bfko_test.xlsx")
print("\nFile structure:")
print(f"  - Title row: Row 1")
print(f"  - Header row: Row 3")
print(f"  - Data rows: {len(df)} employees")
print(f"  - Columns: {len(df.columns)}")
print("\nYou can now upload this file through BFKO Import modal to test the auto-convert feature.")
