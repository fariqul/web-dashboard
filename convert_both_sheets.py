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

# CSV header
header = ['trip_number', 'customer_name', 'trip_destination', 'reason_for_trip',
          'trip_begins_on', 'trip_ends_on', 'paid_amount', 'beneficiary_bank_name']

all_rows = []
seen_trip_numbers = set()  # Track trip numbers to avoid duplicates

# Read both sheets
sheet1_data = pd.read_excel(excel_file, sheet_name='Sheet1', header=0)
sheet1_2_data = pd.read_excel(excel_file, sheet_name='Sheet1 (2)', header=0)

# Process Sheet1
print(f"\nProcessing Sheet1")
print(f"Shape: {sheet1_data.shape}")
print(f"Columns: {list(sheet1_data.columns[:15])}")

for idx, row in sheet1_data.iterrows():
    try:
        # Skip empty rows
        if pd.isna(row.iloc[0]):
            continue
        
        # Column 4: Trip Number
        trip_number = clean_trip_number(row['Trip Number'])
        if not trip_number:
            continue
        
        # Skip if we've already seen this trip number
        if trip_number in seen_trip_numbers:
            print(f"  Row {idx}: Skipping duplicate trip {trip_number}")
            continue
        
        seen_trip_numbers.add(trip_number)
        
        # Column 10: Customer Name
        customer_name = str(row['Customer Name']).strip() if not pd.isna(row['Customer Name']) else ""
        
        # Column 11: Trip Destination (seems to be Excel date, skip for now)
        trip_destination = str(row['Trip Destination']).strip() if not pd.isna(row['Trip Destination']) else ""
        
        # Column 12: Reason for Trip
        reason_for_trip = str(row['Reason for Trip']).strip() if not pd.isna(row['Reason for Trip']) else ""
        
        # Column 13: Trip Begins On
        trip_begins_on = format_date(row['Trip Begins On'])
        
        # Column 14: Trip Ends On
        trip_ends_on = format_date(row['Trip Ends On'])
        
        # Paid Amount
        paid_amount = clean_amount(row['Paid Amount'])
        
        # Beneficiary Bank Name
        beneficiary_bank_name = str(row['Beneficiary Bank Name']).strip() if not pd.isna(row['Beneficiary Bank Name']) else ""
        
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
        print(f"  Error processing Sheet1 row {idx}: {e}")
        continue

# Process Sheet1 (2)
print(f"\nProcessing Sheet1 (2)")
print(f"Shape: {sheet1_2_data.shape}")

for idx, row in sheet1_2_data.iterrows():
    try:
        # Skip empty rows or summary rows
        if pd.isna(row.iloc[0]) or str(row.iloc[0]).startswith('TERBILANG'):
            continue
        
        # Column 1: Trip Number
        trip_number = clean_trip_number(row['Trip Number'])
        if not trip_number:
            continue
        
        # Skip if we've already seen this trip number
        if trip_number in seen_trip_numbers:
            print(f"  Row {idx}: Skipping duplicate trip {trip_number}")
            continue
        
        seen_trip_numbers.add(trip_number)
        
        # Column 2: Customer Name
        customer_name = str(row['Customer Name']).strip() if not pd.isna(row['Customer Name']) else ""
        
        # Column 3: Trip Destination
        trip_destination = str(row['Trip Destination']).strip() if not pd.isna(row['Trip Destination']) else ""
        
        # Column 4: Reason for Trip
        reason_for_trip = str(row['Reason for Trip']).strip() if not pd.isna(row['Reason for Trip']) else ""
        
        # Column 5: Trip Begins On
        trip_begins_on = format_date(row['Trip Begins On'])
        
        # Column 6: Trip Ends On
        trip_ends_on = format_date(row['Trip Ends On'])
        
        # Column 11: Paid Amount
        paid_amount = clean_amount(row['Paid Amount'])
        
        # Column 12: Beneficiary Bank Name
        beneficiary_bank_name = str(row['Beneficiary Bank Name']).strip() if not pd.isna(row['Beneficiary Bank Name']) else ""
        
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
        print(f"  Error processing Sheet1 (2) row {idx}: {e}")
        continue

# Write to CSV
import csv
with open(output_file, 'w', newline='', encoding='utf-8') as f:
    writer = csv.writer(f)
    writer.writerow(header)
    writer.writerows(all_rows)

print(f"\n✓ Successfully converted {len(all_rows)} rows to {output_file}")
print(f"  - From Sheet1: Check count above")
print(f"  - From Sheet1 (2): Check count above")
