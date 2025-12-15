import pandas as pd
import re
import csv

excel_file = r'D:\Bu Intan\Lampiran Ams - Tgl Bayar 17112025.XLSX'

def clean_trip_number(value):
    if pd.isna(value):
        return ''
    trip_str = str(value).strip()
    trip_str = re.sub(r'[^\d]', '', trip_str)
    return trip_str if trip_str else ''

def clean_amount(value):
    if pd.isna(value):
        return '0'
    amount_str = str(value).replace(',', '').replace('.', '').strip()
    amount_str = re.sub(r'[^\d]', '', amount_str)
    return amount_str if amount_str else '0'

def format_date(value):
    if pd.isna(value):
        return ''
    if isinstance(value, str):
        return value
    try:
        if hasattr(value, 'strftime'):
            return value.strftime('%Y-%m-%d')
        else:
            date_obj = pd.to_datetime(value)
            return date_obj.strftime('%Y-%m-%d')
    except:
        return str(value)

# Process each bank sheet
banks = ['MANDIRI', 'BNI', 'BRI', 'BSI']

for bank_name in banks:
    print(f'\nProcessing {bank_name}...')
    
    # Read sheet with header at row 6
    df = pd.read_excel(excel_file, sheet_name=bank_name, header=6)
    print(f'Shape: {df.shape}')
    print(f'Columns: {list(df.columns[:10])}')
    
    rows = []
    for idx, row in df.iterrows():
        try:
            # Skip empty rows
            if pd.isna(row.iloc[0]):
                continue
            
            # Trip Number (column 1)
            trip_number = clean_trip_number(row['Trip Number'])
            if not trip_number:
                continue
            
            # Customer Name (column 2)
            customer_name = str(row['Customer Name']).strip() if not pd.isna(row['Customer Name']) else ''
            
            # Trip Destination (column 3)
            trip_destination = str(row['Trip Destination']).strip() if not pd.isna(row['Trip Destination']) else ''
            
            # Reason For Trip (column 4)
            reason_for_trip = str(row['Reason For Trip']).strip() if not pd.isna(row['Reason For Trip']) else ''
            
            # Trip Begins On (column 5)
            trip_begins_on = format_date(row['Trip Begins On'])
            
            # Trip Ends On (column 6)
            trip_ends_on = format_date(row['Trip Ends On'])
            
            # Paid Amount (column 11)
            paid_amount = clean_amount(row['Paid Amount'])
            
            # Beneficiary Bank Name - use the sheet name
            beneficiary_bank_name = bank_name
            
            csv_row = [
                trip_number,
                customer_name,
                trip_destination,
                reason_for_trip,
                trip_begins_on,
                trip_ends_on,
                paid_amount,
                beneficiary_bank_name
            ]
            
            rows.append(csv_row)
            
        except Exception as e:
            print(f'  Error on row {idx}: {e}')
            continue
    
    # Save to CSV
    output_file = f'data/sppd/sppd_november_2025_{bank_name.lower()}.csv'
    with open(output_file, 'w', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        writer.writerow(['trip_number', 'customer_name', 'trip_destination', 'reason_for_trip', 
                        'trip_begins_on', 'trip_ends_on', 'paid_amount', 'beneficiary_bank_name'])
        writer.writerows(rows)
    
    print(f'✓ Saved {len(rows)} trips to {output_file}')

print('\n✓ All bank sheets converted successfully!')
