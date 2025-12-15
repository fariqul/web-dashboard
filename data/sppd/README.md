# SPPD Data CSV Format

## File Format

**File name pattern:** `sppd_[bulan]_[tahun].csv`
Example: `sppd_desember_2025.csv`

## CSV Structure

The CSV file must have exactly 8 columns in this order:

| Column # | Column Name | Type | Format | Required | Description |
|----------|-------------|------|--------|----------|-------------|
| 1 | trip_number | String | SP###/MONTH/YEAR | Yes | Unique trip identifier (e.g., SP001/XII/2025) |
| 2 | customer_name | String | Text | Yes | Employee name making the trip |
| 3 | trip_destination | String | Origin - Destination | Yes | Full route (e.g., "Makassar - Jakarta") |
| 4 | reason_for_trip | String | Text | Yes | Purpose of the trip |
| 5 | trip_begins_on | Date | YYYY-MM-DD | Yes | Start date (e.g., 2025-12-01) |
| 6 | trip_ends_on | Date | YYYY-MM-DD | Yes | End date (e.g., 2025-12-03) |
| 7 | paid_amount | Number | Integer | Yes | Payment amount in Rupiah without separators |
| 8 | beneficiary_bank_name | String | Text | No | Bank name (e.g., BNI, BRI, Mandiri, BCA) |

## Important Rules

### ✅ Required Format

1. **Header Row:** First line must contain exact column names as shown above
2. **Date Format:** Must be `YYYY-MM-DD` (ISO 8601)
3. **Number Format:** No thousands separators, no decimals, no currency symbol
   - ✅ Correct: `5500000`
   - ❌ Wrong: `5.500.000` or `5,500,000` or `Rp 5.500.000`
4. **Trip Number:** Must be unique across all records
5. **Trip Destination:** Format must be "Origin - Destination" with space-dash-space
6. **End Date:** Must be >= Start Date

### 📝 Example Row

```csv
SP001/XII/2025,Ahmad Firmansyah,Makassar - Jakarta,Rapat Koordinasi Tahunan,2025-12-01,2025-12-03,5500000,BNI
```

## Field Details

### trip_number
- Format: `SP###/ROMAN_MONTH/YEAR`
- Examples: 
  - `SP001/XII/2025` (December 2025)
  - `SP045/I/2025` (January 2025)
  - `SP100/VI/2025` (June 2025)
- Must be unique per transaction
- Used to extract month/year for sheet grouping

### customer_name
- Full name of the employee
- No special format required
- Examples: "Ahmad Firmansyah", "Siti Nurhaliza"

### trip_destination
- Must follow: `[Origin] - [Destination]`
- Space before and after the dash is required
- System will parse this into `origin` and `destination` fields
- Examples:
  - ✅ `Makassar - Jakarta`
  - ✅ `Jakarta - Surabaya`
  - ❌ `Makassar-Jakarta` (no spaces)
  - ❌ `Makassar to Jakarta` (wrong separator)

### reason_for_trip
- Free text describing the purpose
- Examples:
  - "Rapat Koordinasi Tahunan"
  - "Pelatihan SDM"
  - "Audit Internal"

### trip_begins_on & trip_ends_on
- **MUST** use `YYYY-MM-DD` format
- `trip_ends_on` must be >= `trip_begins_on`
- System automatically calculates `duration_days`
- Examples:
  - ✅ `2025-12-01`
  - ✅ `2025-01-15`
  - ❌ `01/12/2025` (wrong format)
  - ❌ `12-01-2025` (wrong format)

### paid_amount
- Integer only (no decimals needed for Rupiah)
- No formatting characters
- Examples:
  - ✅ `5500000` (5.5 million)
  - ✅ `4200000` (4.2 million)
  - ❌ `5.500.000`
  - ❌ `5,500,000`
  - ❌ `Rp 5500000`

### beneficiary_bank_name
- Common banks: BNI, BRI, Mandiri, BCA, CIMB, Permata, etc.
- Optional field (can be empty)
- Used for analysis by bank

## Import Process

1. Go to `/sppd` page
2. Click "📥 Import CSV" button
3. Select your CSV file
4. Optionally specify sheet name (default: extracted from trip_number)
5. Check "Update existing records" if you want to update duplicates
6. Click "Import"

## Validation

The system will check:
- ✅ Correct number of columns (8)
- ✅ Valid date format
- ✅ End date >= Start date
- ✅ trip_number is unique
- ✅ paid_amount is a valid number
- ✅ Required fields are not empty

## Example Complete CSV

See `sppd_desember_2025.csv` in this folder for a working example with 10 sample transactions.

## Sheet Naming

If no sheet name is provided during import, the system extracts it from `trip_number`:

| Trip Number | Extracted Sheet Name |
|-------------|---------------------|
| SP001/XII/2025 | Desember 2025 |
| SP045/I/2025 | Januari 2025 |
| SP100/VI/2025 | Juni 2025 |

Roman numeral to month mapping:
- I = Januari, II = Februari, III = Maret, IV = April
- V = Mei, VI = Juni, VII = Juli, VIII = Agustus
- IX = September, X = Oktober, XI = November, XII = Desember

## Troubleshooting

**Problem:** Import fails with "Invalid CSV format"
- **Solution:** Check that header matches exactly: `trip_number,customer_name,trip_destination,reason_for_trip,trip_begins_on,trip_ends_on,paid_amount,beneficiary_bank_name`

**Problem:** Date parsing error
- **Solution:** Ensure dates are in `YYYY-MM-DD` format, not `DD/MM/YYYY` or `MM/DD/YYYY`

**Problem:** Duplicate trip_number
- **Solution:** Each trip_number must be unique. Use sequential numbering (SP001, SP002, SP003...)

**Problem:** Amount shows incorrectly
- **Solution:** Remove all thousands separators and currency symbols from the CSV
