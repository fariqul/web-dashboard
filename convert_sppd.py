import csv
import re
from datetime import datetime, timedelta

def excel_date_to_datetime(excel_date):
    """Convert Excel date number to datetime"""
    try:
        # Excel date starts from 1900-01-01
        base_date = datetime(1899, 12, 30)
        return base_date + timedelta(days=int(excel_date))
    except:
        return None

def format_date(date_obj):
    """Format date to YYYY-MM-DD"""
    if date_obj:
        return date_obj.strftime('%Y-%m-%d')
    return ''

def parse_trip_number(trip_number, date_obj, counter):
    """
    Preserve original trip number from source file
    No transformation - use as-is
    """
    if not trip_number:
        return ""
    # Clean whitespace and remove decimal points if present
    trip_num = str(trip_number).strip()
    if '.' in trip_num:
        trip_num = trip_num.split('.')[0]
    return trip_num

# Read input file
input_file = r"d:\Bu Intan\Lampiran Ams - Tgl Bayar 17112025.XLSX"
output_file = r"d:\Bu Intan\Bu Intan\data\sppd\sppd_november_2025.csv"

# Read the CSV
with open(input_file, 'r', encoding='utf-8') as f:
    content = f.read()

# Parse manually since the file has unusual format
lines = content.strip().split('\n')

# Find header row
header_row = None
for i, line in enumerate(lines):
    if 'Trip Number' in line or 'Customer Name' in line:
        header_row = i
        break

if header_row is None:
    print("Header not found")
    exit()

# Parse data rows
output_data = []
counter = 1  # Counter for trip numbers

for i in range(header_row + 1, len(lines)):
    line = lines[i].strip()
    if not line or line.count(',') < 10:
        continue
    
    # Split by comma but handle quoted fields
    parts = []
    current = ''
    in_quotes = False
    
    for char in line:
        if char == '"':
            in_quotes = not in_quotes
        elif char == ',' and not in_quotes:
            parts.append(current.strip())
            current = ''
        else:
            current += char
    parts.append(current.strip())
    
    if len(parts) < 14:
        continue
    
    # Extract fields - CORRECT indices
    # 0=Group ID, 1=Trip Number, 2=Customer Name, 3=Trip Destination, 
    # 4=Reason, 5=Begins, 6=Ends, 7=Tanggal Bayar, 8=Company Code,
    # 9=House Bank, 10=Currency, 11=Amount, 12=Beneficiary Bank, 13=Account, 14=Name
    try:
        trip_number = parts[1].strip()  # Column 1 is Trip Number
        customer_name = parts[2].strip()
        trip_destination = parts[3].strip()
        reason_for_trip = parts[4].strip()
        trip_begins_str = parts[5].strip()
        trip_ends_str = parts[6].strip()
        paid_amount = parts[11].strip()
        beneficiary_bank = parts[12].strip()
        
        # Skip if essential fields are empty
        if not trip_number or not customer_name:
            continue
        
        # Convert Excel dates
        trip_begins_on = ''
        trip_ends_on = ''
        date_obj_for_trip_number = None
        
        if trip_begins_str and trip_begins_str.isdigit():
            date_obj = excel_date_to_datetime(trip_begins_str)
            trip_begins_on = format_date(date_obj)
            date_obj_for_trip_number = date_obj
        
        if trip_ends_str and trip_ends_str.isdigit():
            date_obj = excel_date_to_datetime(trip_ends_str)
            trip_ends_on = format_date(date_obj)
        
        # Format trip number with roman numerals using counter
        formatted_trip_number = parse_trip_number(trip_number, date_obj_for_trip_number, counter)
        counter += 1
        
        # Clean amount - remove non-digits
        clean_amount = re.sub(r'[^\d]', '', paid_amount)
        if not clean_amount:
            clean_amount = '0'
        
        # Create output row
        output_row = [
            formatted_trip_number,
            customer_name,
            trip_destination,
            reason_for_trip,
            trip_begins_on,
            trip_ends_on,
            clean_amount,
            beneficiary_bank
        ]
        
        output_data.append(output_row)
        
    except Exception as e:
        print(f"Error processing line {i}: {e}")
        continue

# Write output CSV
with open(output_file, 'w', newline='', encoding='utf-8') as f:
    writer = csv.writer(f)
    # Write header
    writer.writerow(['trip_number', 'customer_name', 'trip_destination', 'reason_for_trip', 
                     'trip_begins_on', 'trip_ends_on', 'paid_amount', 'beneficiary_bank_name'])
    # Write data
    writer.writerows(output_data)

print(f"Conversion complete! {len(output_data)} rows written to {output_file}")
print("\nSample data:")
for i, row in enumerate(output_data[:3]):
    print(f"Row {i+1}: {row}")
