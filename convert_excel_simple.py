import pandas as pd
import re
from datetime import datetime

def clean_trip_number(trip_num):
    """Preserve original trip number"""
    if pd.isna(trip_num):
        return ""
    trip_num = str(trip_num).strip()
    if '.' in trip_num:
        trip_num = trip_num.split('.')[0]
    return trip_num

def clean_amount(amount):
    """Clean currency amount"""
    if pd.isna(amount):
        return "0"
    amount_str = str(amount).strip()
    amount_str = re.sub(r'[Rp,.\s]', '', amount_str)
    return amount_str if amount_str else "0"

def format_date(date_val):
    """Format date to YYYY-MM-DD"""
    if pd.isna(date_val):
        return ""
    
    # If it's already a datetime object
    if isinstance(date_val, (datetime, pd.Timestamp)):
        return date_val.strftime('%Y-%m-%d')
    
    # If it's a number (Excel serial date)
    try:
        # Try to convert as Excel date number
        if isinstance(date_val, (int, float)):
            # Excel dates are days since 1899-12-30
            excel_epoch = datetime(1899, 12, 30)
            delta = pd.Timedelta(days=date_val)
            actual_date = excel_epoch + delta
            return actual_date.strftime('%Y-%m-%d')
    except:
        pass
    
    # Fallback: return as string
    return str(date_val).strip()

# Read Excel file
excel_file = r"D:\Bu Intan\Lampiran Ams - Tgl Bayar 17112025.XLSX"
output_file = r"D:\Bu Intan\Bu Intan\data\sppd\sppd_november_2025.csv"

# Read Sheet1 (2) only - this has all the complete data
# Header is at row 0
excel_data = pd.read_excel(excel_file, sheet_name='Sheet1 (2)', header=0)

# CSV header
header = ['trip_number', 'customer_name', 'trip_destination', 'reason_for_trip',
          'trip_begins_on', 'trip_ends_on', 'paid_amount', 'beneficiary_bank_name']

all_rows = []

print(f"\nProcessing Sheet1 (2)")
print(f"Shape: {excel_data.shape}")

for idx, row in excel_data.iterrows():
    try:
        # Skip empty rows or summary rows
        if pd.isna(row.iloc[0]) or str(row.iloc[0]).startswith('TERBILANG'):
            continue
        
        # Column 1: Trip Number
        trip_number = clean_trip_number(row.iloc[1])
        if not trip_number:
            continue
        
        # Column 2: Customer Name
        customer_name = str(row.iloc[2]).strip() if not pd.isna(row.iloc[2]) else ""
        
        # Column 3: Trip Destination
        trip_destination = str(row.iloc[3]).strip() if not pd.isna(row.iloc[3]) else ""
        
        # Column 4: Reason for Trip
        reason_for_trip = str(row.iloc[4]).strip() if not pd.isna(row.iloc[4]) else ""
        
        # Column 5: Trip Begins On
        trip_begins_on = format_date(row.iloc[5])
        
        # Column 6: Trip Ends On
        trip_ends_on = format_date(row.iloc[6])
        
        # Column 11: Paid Amount
        paid_amount = clean_amount(row.iloc[11])
        
        # Column 12: Beneficiary Bank Name
        beneficiary_bank_name = str(row.iloc[12]).strip() if not pd.isna(row.iloc[12]) else ""
        
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
        
        all_rows.append(csv_row)
        print(f"  Row {idx}: {trip_number} - {customer_name}")
        
    except Exception as e:
        print(f"  Error processing row {idx}: {e}")
        continue

# Write to CSV
import csv
with open(output_file, 'w', newline='', encoding='utf-8') as f:
    writer = csv.writer(f)
    writer.writerow(header)
    writer.writerows(all_rows)

print(f"\n✓ Successfully converted {len(all_rows)} rows to {output_file}")
