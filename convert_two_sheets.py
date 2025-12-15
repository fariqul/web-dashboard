import pandas as pd
import re
import csv

excel_file = r'D:\Bu Intan\Lampiran Ams - Tgl Bayar 17112025.XLSX'

def clean_trip_number(value):
    if pd.isna(value):
        return ''
    # Handle scientific notation
    if isinstance(value, float):
        value = int(value)
    trip_str = str(value).strip()
    trip_str = re.sub(r'[^\d]', '', trip_str)
    # Ensure 10 digits
    if len(trip_str) > 10:
        trip_str = trip_str[:10]
    return trip_str if trip_str else ''

def clean_amount(value):
    if pd.isna(value):
        return '0'
    # Handle scientific notation
    if isinstance(value, float):
        value = int(value)
    amount_str = str(value).replace(',', '').replace('.', '').strip()
    amount_str = re.sub(r'[^\d]', '', amount_str)
    return amount_str if amount_str else '0'

def format_date(value):
    if pd.isna(value):
        return ''
    try:
        if isinstance(value, str):
            return value
        # Check if it's a datetime object
        if hasattr(value, 'year'):
            return value.strftime('%Y-%m-%d')
        # Handle Excel serial date number
        if isinstance(value, (int, float)):
            # Excel serial dates (days since 1900-01-01)
            base_date = pd.Timestamp('1899-12-30')  # Excel's epoch
            date_obj = base_date + pd.Timedelta(days=int(value))
            return date_obj.strftime('%Y-%m-%d')
        # Try to convert to datetime
        date_obj = pd.to_datetime(value, errors='coerce')
        if pd.isna(date_obj):
            return ''
        return date_obj.strftime('%Y-%m-%d')
    except:
        return ''

# === PROCESS SHEET1 ===
print('Processing Sheet1...')
df1 = pd.read_excel(excel_file, sheet_name='Sheet1', header=0)
print(f'Shape: {df1.shape}')

rows1 = []
for idx, row in df1.iterrows():
    try:
        if pd.isna(row['Trip Number']):
            continue
        
        trip_number = clean_trip_number(row['Trip Number'])
        if not trip_number:
            continue
        
        customer_name = str(row['Customer Name']).strip() if not pd.isna(row['Customer Name']) else ''
        trip_destination = str(row['Trip Destination']).strip() if not pd.isna(row['Trip Destination']) else ''
        reason_for_trip = str(row['Reason for Trip']).strip() if not pd.isna(row['Reason for Trip']) else ''
        trip_begins_on = format_date(row['Trip Begins On'])
        trip_ends_on = format_date(row['Trip Ends On'])
        paid_amount = clean_amount(row['Paid Amount'])
        beneficiary_bank_name = str(row['Beneficiary Bank Name']).strip() if not pd.isna(row['Beneficiary Bank Name']) else ''
        
        rows1.append([trip_number, customer_name, trip_destination, reason_for_trip, 
                     trip_begins_on, trip_ends_on, paid_amount, beneficiary_bank_name])
        
    except Exception as e:
        print(f'  Error row {idx}: {e}')

output1 = r'D:\Bu Intan\Bu Intan\data\sppd\sppd_november_2025_part1.csv'
with open(output1, 'w', newline='', encoding='utf-8') as f:
    writer = csv.writer(f)
    writer.writerow(['trip_number', 'customer_name', 'trip_destination', 'reason_for_trip',
                    'trip_begins_on', 'trip_ends_on', 'paid_amount', 'beneficiary_bank_name'])
    writer.writerows(rows1)

print(f'✓ Sheet1: {len(rows1)} trips saved to part1.csv')

# === PROCESS SHEET1 (2) ===
print('\nProcessing Sheet1 (2)...')
df2 = pd.read_excel(excel_file, sheet_name='Sheet1 (2)', header=0)
print(f'Shape: {df2.shape}')

rows2 = []
for idx, row in df2.iterrows():
    try:
        if pd.isna(row['Trip Number']):
            continue
        
        trip_number = clean_trip_number(row['Trip Number'])
        if not trip_number:
            continue
        
        customer_name = str(row['Customer Name']).strip() if not pd.isna(row['Customer Name']) else ''
        trip_destination = str(row['Trip Destination']).strip() if not pd.isna(row['Trip Destination']) else ''
        reason_for_trip = str(row['Reason for Trip']).strip() if not pd.isna(row['Reason for Trip']) else ''
        trip_begins_on = format_date(row['Trip Begins On'])
        trip_ends_on = format_date(row['Trip Ends On'])
        paid_amount = clean_amount(row['Paid Amount'])
        beneficiary_bank_name = str(row['Beneficiary Bank Name']).strip() if not pd.isna(row['Beneficiary Bank Name']) else ''
        
        rows2.append([trip_number, customer_name, trip_destination, reason_for_trip,
                     trip_begins_on, trip_ends_on, paid_amount, beneficiary_bank_name])
        
    except Exception as e:
        print(f'  Error row {idx}: {e}')

output2 = r'D:\Bu Intan\Bu Intan\data\sppd\sppd_november_2025_part2.csv'
with open(output2, 'w', newline='', encoding='utf-8') as f:
    writer = csv.writer(f)
    writer.writerow(['trip_number', 'customer_name', 'trip_destination', 'reason_for_trip',
                    'trip_begins_on', 'trip_ends_on', 'paid_amount', 'beneficiary_bank_name'])
    writer.writerows(rows2)

print(f'✓ Sheet1 (2): {len(rows2)} trips saved to part2.csv')
print(f'\n✓ Total: {len(rows1) + len(rows2)} trips from both sheets')
