import openpyxl
from datetime import datetime
import csv
import re

def format_date(date_val):
    """Convert date to YYYY-MM-DD format"""
    if isinstance(date_val, datetime):
        return date_val.strftime('%Y-%m-%d')
    elif isinstance(date_val, str):
        # Try to parse string date
        try:
            dt = datetime.strptime(date_val, '%Y-%m-%d')
            return dt.strftime('%Y-%m-%d')
        except:
            return ''
    return ''

def get_roman_month(month):
    """Convert month number to Roman numeral"""
    roman_months = {
        1: 'I', 2: 'II', 3: 'III', 4: 'IV', 5: 'V', 6: 'VI',
        7: 'VII', 8: 'VIII', 9: 'IX', 10: 'X', 11: 'XI', 12: 'XII'
    }
    return roman_months.get(month, 'I')

# Load Excel file
input_file = r"d:\Bu Intan\Lampiran_Ams_Tgl_Bayar_17112025.csv"

# First, let's check if it's actually an Excel file
print("Trying to open as Excel file...")
try:
    wb = openpyxl.load_workbook(input_file, data_only=True)
    print(f"Excel file loaded successfully!")
    print(f"Available sheets: {wb.sheetnames}")
    
    all_data = []
    
    # Process each sheet
    for sheet_name in wb.sheetnames[:2]:  # Process first 2 sheets
        print(f"\n=== Processing Sheet: {sheet_name} ===")
        ws = wb[sheet_name]
        
        # Find header row
        header_row = None
        for row_idx, row in enumerate(ws.iter_rows(values_only=True), 1):
            if row and any('Trip Number' in str(cell) for cell in row if cell):
                header_row = row_idx
                print(f"Header found at row {header_row}")
                print(f"Headers: {[cell for cell in row if cell]}")
                break
        
        if not header_row:
            print(f"No header found in {sheet_name}, skipping...")
            continue
        
        # Get column indices
        header = list(ws.iter_rows(min_row=header_row, max_row=header_row, values_only=True))[0]
        
        col_indices = {}
        for idx, cell in enumerate(header):
            if cell:
                cell_str = str(cell).strip()
                if 'Trip Number' in cell_str:
                    col_indices['trip_number'] = idx
                elif 'Customer Name' in cell_str:
                    col_indices['customer_name'] = idx
                elif 'Trip Destination' in cell_str:
                    col_indices['trip_destination'] = idx
                elif 'Reason For Trip' in cell_str or 'Reason' in cell_str:
                    col_indices['reason'] = idx
                elif 'Trip Begins' in cell_str or 'Begins On' in cell_str:
                    col_indices['begins'] = idx
                elif 'Trip Ends' in cell_str or 'Ends On' in cell_str:
                    col_indices['ends'] = idx
                elif 'Paid Amount' in cell_str:
                    col_indices['amount'] = idx
                elif 'Beneficiary Bank' in cell_str:
                    col_indices['bank'] = idx
        
        print(f"Column indices: {col_indices}")
        
        # Process data rows
        row_count = 0
        for row in ws.iter_rows(min_row=header_row + 1, values_only=True):
            if not row or not any(row):
                continue
            
            try:
                # Extract values
                trip_number_raw = str(row[col_indices.get('trip_number', 1)] or '').strip()
                customer_name = str(row[col_indices.get('customer_name', 2)] or '').strip()
                trip_destination = str(row[col_indices.get('trip_destination', 3)] or '').strip()
                reason = str(row[col_indices.get('reason', 4)] or '').strip()
                begins_val = row[col_indices.get('begins', 5)]
                ends_val = row[col_indices.get('ends', 6)]
                amount_val = row[col_indices.get('amount', 11)]
                bank = str(row[col_indices.get('bank', 12)] or '').strip()
                
                # Skip if essential fields are empty
                if not trip_number_raw or not customer_name or trip_number_raw == 'None':
                    continue
                
                # Format dates
                begins_date = format_date(begins_val)
                ends_date = format_date(ends_val)
                
                if not begins_date:
                    continue
                
                # Format trip number with roman numerals
                # Extract date info
                date_obj = None
                if isinstance(begins_val, datetime):
                    date_obj = begins_val
                elif begins_date:
                    try:
                        date_obj = datetime.strptime(begins_date, '%Y-%m-%d')
                    except:
                        pass
                
                if date_obj:
                    roman_month = get_roman_month(date_obj.month)
                    year = date_obj.year
                    
                    # Extract sequential number from trip_number
                    # Format: take last 3-4 digits or create sequential
                    numbers = re.findall(r'\d+', trip_number_raw)
                    if numbers:
                        seq_num = numbers[-1][-3:].zfill(3)  # Take last 3 digits
                    else:
                        seq_num = str(row_count + 1).zfill(3)
                    
                    formatted_trip_number = f"SP{seq_num}/{roman_month}/{year}"
                else:
                    formatted_trip_number = trip_number_raw
                
                # Clean amount
                if amount_val:
                    amount_str = str(amount_val).replace(',', '').replace('.', '').strip()
                    amount_clean = re.sub(r'[^\d]', '', amount_str)
                else:
                    amount_clean = '0'
                
                # Add to data
                all_data.append([
                    formatted_trip_number,
                    customer_name,
                    trip_destination,
                    reason,
                    begins_date,
                    ends_date,
                    amount_clean,
                    bank
                ])
                
                row_count += 1
                
            except Exception as e:
                print(f"Error processing row: {e}")
                continue
        
        print(f"Processed {row_count} rows from {sheet_name}")
    
    # Write output
    output_file = r"d:\Bu Intan\Bu Intan\data\sppd\sppd_november_2025.csv"
    with open(output_file, 'w', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        writer.writerow(['trip_number', 'customer_name', 'trip_destination', 'reason_for_trip', 
                         'trip_begins_on', 'trip_ends_on', 'paid_amount', 'beneficiary_bank_name'])
        writer.writerows(all_data)
    
    print(f"\n✅ Conversion complete! {len(all_data)} total rows written to {output_file}")
    print("\n📋 Sample data:")
    for i, row in enumerate(all_data[:5]):
        print(f"  {i+1}. {row[0]} | {row[1]} | {row[2][:30]}... | {row[4]} to {row[5]}")
    
except Exception as e:
    print(f"Error: {e}")
    print("\nFile appears to be CSV, not Excel. Please check the file.")
