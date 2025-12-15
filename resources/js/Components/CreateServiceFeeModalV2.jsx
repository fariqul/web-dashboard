import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import toast from 'react-hot-toast';

export default function CreateServiceFeeModal({ isOpen, onClose, availableSheets = [] }) {
    const [activeTab, setActiveTab] = useState('manual');
    const [serviceType, setServiceType] = useState('hotel');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState({});

    const [formData, setFormData] = useState({
        booking_id: '',
        transaction_time: '',
        sheet: 'auto', // Default to auto-generate
        transaction_amount: '',
        employee_name: '',
        // Hotel
        hotel_name: '',
        room_type: '',
        // Flight
        route: '',
        trip_type: 'One Way',
        pax: '1',
        airline_id: '',
        booker_email: ''
    });

    // CSV Import State
    const [csvFiles, setCSVFiles] = useState([]); // Changed to array
    const [csvPreview, setCSVPreview] = useState([]);
    const [csvOptions, setCsvOptions] = useState({
        autoPreprocess: true,
        skipSummary: true,
        forceUpdate: false
    });

    if (!isOpen) return null;

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
        if (errors[name]) {
            setErrors(prev => ({ ...prev, [name]: null }));
        }
    };

    const calculateFees = (amount) => {
        const serviceFee = Math.floor(parseFloat(amount || 0) * 0.01);
        const vat = Math.floor(serviceFee * 0.11);
        return { serviceFee, vat, total: serviceFee + vat };
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setIsSubmitting(true);
        setErrors({});

        const fees = calculateFees(formData.transaction_amount);

        router.post('/service-fee/store', {
            ...formData,
            service_type: serviceType,
            base_amount: fees.serviceFee,
            service_fee: fees.serviceFee,
            vat: fees.vat,
            total_tagihan: fees.total
        }, {
            onSuccess: () => {
                setFormData({
                    booking_id: '', transaction_time: '', sheet: 'auto', transaction_amount: '',
                    employee_name: '', status: 'settlement', hotel_name: '', room_type: '', route: '',
                    trip_type: 'One Way', pax: '1', airline_id: '', booker_email: ''
                });
                onClose();
                // Let Inertia handle redirect to preserve flash messages
            },
            onError: (err) => {
                setErrors(err);
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    };

    const handleCSVUpload = (e) => {
        const files = Array.from(e.target.files);
        if (!files.length) return;

        // Validate all files are CSV or Excel
        const invalidFiles = files.filter(f => {
            const ext = f.name.toLowerCase();
            return !ext.endsWith('.csv') && !ext.endsWith('.xlsx') && !ext.endsWith('.xls');
        });
        if (invalidFiles.length > 0) {
            setErrors({ csv: `Invalid files: ${invalidFiles.map(f => f.name).join(', ')}. Only CSV or Excel files allowed.` });
            return;
        }

        setCSVFiles(files);
        setErrors({});
        
        // Preview first file's first 5 rows (only for CSV, skip for Excel)
        if (files[0].name.toLowerCase().endsWith('.csv')) {
            const reader = new FileReader();
            reader.onload = (event) => {
                const text = event.target.result;
                const rows = text.split('\n').slice(0, 6);
                const preview = rows.map(row => row.split(','));
                setCSVPreview(preview);
            };
            reader.readAsText(files[0]);
        } else {
            // For Excel files, show a simple message instead of preview
            setCSVPreview([['Excel file detected - preview not available']]);
        }
    };

    const handleRemoveFile = (index) => {
        setCSVFiles(prev => prev.filter((_, i) => i !== index));
    };

    const handleCSVImport = (e) => {
        e.preventDefault();
        if (!csvFiles.length) {
            setErrors({ csv: 'Please select at least one CSV file' });
            return;
        }

        setIsSubmitting(true);
        const formData = new FormData();
        
        // Append all files
        csvFiles.forEach((file, index) => {
            formData.append(`csv_files[${index}]`, file);
        });
        
        formData.append('auto_preprocess', csvOptions.autoPreprocess ? '1' : '0');
        formData.append('skip_summary', csvOptions.skipSummary ? '1' : '0');
        formData.append('force_update', csvOptions.forceUpdate ? '1' : '0');

        router.post('/service-fee/import-csv', formData, {
            forceFormData: true,
            onSuccess: (page) => {
                setCSVFiles([]);
                setCSVPreview([]);
                onClose();
                
                // Show success toast
                toast.success('CSV import completed successfully!', {
                    duration: 4000,
                    position: 'top-right',
                });
            },
            onError: (err) => {
                setErrors(err);
                toast.error('Import failed. Please check the errors.', {
                    duration: 4000,
                    position: 'top-right',
                });
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    };

    const fees = calculateFees(formData.transaction_amount);

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={onClose}></div>

                <div className="relative bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                    {/* Header */}
                    <div className="bg-gradient-to-r from-cyan-500 to-blue-500 px-6 py-4 sticky top-0 z-10">
                        <div className="flex items-center justify-between">
                            <h3 className="text-xl font-bold text-white">➕ Add New Service Fee Data</h3>
                            <button onClick={onClose} className="text-white hover:text-gray-200">
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {/* Tab Navigation */}
                    <div className="border-b border-gray-200 bg-gray-50">
                        <nav className="flex -mb-px">
                            <button type="button" onClick={() => setActiveTab('manual')}
                                className={`w-1/2 py-4 text-center border-b-2 font-medium transition ${
                                    activeTab === 'manual' ? 'border-cyan-500 text-cyan-600 bg-white' : 'border-transparent text-gray-500'
                                }`}>
                                ✍️ Manual Entry
                            </button>
                            <button type="button" onClick={() => setActiveTab('csv')}
                                className={`w-1/2 py-4 text-center border-b-2 font-medium transition ${
                                    activeTab === 'csv' ? 'border-cyan-500 text-cyan-600 bg-white' : 'border-transparent text-gray-500'
                                }`}>
                                📄 Import CSV
                            </button>
                        </nav>
                    </div>

                    {activeTab === 'manual' ? (
                        <form onSubmit={handleSubmit} className="p-6">
                        {/* Service Type */}
                        <div className="mb-6">
                            <label className="block text-sm font-medium mb-3">Service Type *</label>
                            <div className="flex gap-4">
                                <button type="button" onClick={() => setServiceType('hotel')}
                                    className={`flex-1 py-3 px-4 rounded-lg border-2 transition ${serviceType === 'hotel' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'}`}>
                                    <div className="text-2xl mb-1">🏨</div>
                                    <div className="font-semibold">Hotel</div>
                                </button>
                                <button type="button" onClick={() => setServiceType('flight')}
                                    className={`flex-1 py-3 px-4 rounded-lg border-2 transition ${serviceType === 'flight' ? 'border-green-500 bg-green-50' : 'border-gray-200'}`}>
                                    <div className="text-2xl mb-1">✈️</div>
                                    <div className="font-semibold">Flight</div>
                                </button>
                            </div>
                        </div>

                        {/* Common Fields */}
                        <div className="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label className="block text-sm font-medium mb-1">Booking ID *</label>
                                <input type="text" name="booking_id" value={formData.booking_id} onChange={handleInputChange}
                                    className={`w-full px-3 py-2 border rounded-lg ${errors.booking_id ? 'border-red-500' : 'border-gray-300'}`}
                                    placeholder="e.g., HL123456789" required />
                                {errors.booking_id && <p className="text-red-500 text-xs mt-1">{errors.booking_id}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-1">Transaction Date *</label>
                                <input type="datetime-local" name="transaction_time" value={formData.transaction_time} onChange={handleInputChange}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg" required />
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-1">Sheet/Month *</label>
                                <div className="relative">
                                    <select name="sheet" value={formData.sheet} onChange={handleInputChange}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg appearance-none pr-8" required>
                                        <option value="auto">Auto (from Transaction Date)</option>
                                        <optgroup label="Existing Sheets">
                                            {availableSheets?.map(sheet => <option key={sheet} value={sheet}>{sheet}</option>)}
                                        </optgroup>
                                    </select>
                                    <svg className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" 
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                                {formData.sheet === 'auto' && formData.transaction_time && (
                                    <p className="text-xs text-blue-600 mt-1">
                                        💡 Will create: {new Date(formData.transaction_time).toLocaleString('id-ID', { month: 'long', year: 'numeric' })}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-1">Transaction Amount *</label>
                                <input type="number" name="transaction_amount" value={formData.transaction_amount} onChange={handleInputChange}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="1000000" required />
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-1">Status *</label>
                                <select name="status" value={formData.status} onChange={handleInputChange}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="settlement">Settlement</option>
                                    <option value="pending">Pending</option>
                                    <option value="issued">Issued</option>
                                </select>
                            </div>
                        </div>

                        {/* Hotel Fields */}
                        {serviceType === 'hotel' && (
                            <div className="bg-blue-50 rounded-lg p-4 mb-4">
                                <h4 className="font-semibold mb-3">🏨 Hotel Information</h4>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium mb-1">Hotel Name *</label>
                                        <input type="text" name="hotel_name" value={formData.hotel_name} onChange={handleInputChange}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg" required />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium mb-1">Room Type *</label>
                                        <input type="text" name="room_type" value={formData.room_type} onChange={handleInputChange}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg" required />
                                    </div>
                                    <div className="col-span-2">
                                        <label className="block text-sm font-medium mb-1">Employee Name *</label>
                                        <input type="text" name="employee_name" value={formData.employee_name} onChange={handleInputChange}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg" required />
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Flight Fields */}
                        {serviceType === 'flight' && (
                            <div className="bg-green-50 rounded-lg p-4 mb-4">
                                <h4 className="font-semibold mb-3">✈️ Flight Information</h4>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium mb-1">Route *</label>
                                        <input type="text" name="route" value={formData.route} onChange={handleInputChange}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="CGK_DPS" required />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium mb-1">Trip Type *</label>
                                        <select name="trip_type" value={formData.trip_type} onChange={handleInputChange}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                            <option value="One Way">One Way</option>
                                            <option value="Return">Return</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium mb-1">Pax *</label>
                                        <input type="number" name="pax" value={formData.pax} onChange={handleInputChange}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg" min="1" required />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium mb-1">Airline ID *</label>
                                        <input type="text" name="airline_id" value={formData.airline_id} onChange={handleInputChange}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="GA" required />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium mb-1">Booker Email</label>
                                        <input type="email" name="booker_email" value={formData.booker_email} onChange={handleInputChange}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg" />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium mb-1">Employee Name *</label>
                                        <input type="text" name="employee_name" value={formData.employee_name} onChange={handleInputChange}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg" required />
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Fee Summary */}
                        {formData.transaction_amount && (
                            <div className="bg-gray-50 p-4 rounded-lg mb-4">
                                <h4 className="font-semibold mb-2">💰 Calculated Fees</h4>
                                <div className="space-y-1 text-sm">
                                    <div className="flex justify-between">
                                        <span>Service Fee (1%):</span>
                                        <span className="font-semibold">Rp {fees.serviceFee.toLocaleString('id-ID')}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>VAT (11%):</span>
                                        <span className="font-semibold">Rp {fees.vat.toLocaleString('id-ID')}</span>
                                    </div>
                                    <hr />
                                    <div className="flex justify-between text-base">
                                        <span className="font-bold">Total Tagihan:</span>
                                        <span className="font-bold text-cyan-600">Rp {fees.total.toLocaleString('id-ID')}</span>
                                    </div>
                                </div>
                            </div>
                        )}

                            {/* Footer */}
                            <div className="flex justify-end gap-3 pt-4 border-t">
                                <button type="button" onClick={onClose}
                                    className="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100" disabled={isSubmitting}>
                                    Cancel
                                </button>
                                <button type="submit" disabled={isSubmitting}
                                    className="px-6 py-2 bg-gradient-to-r from-cyan-500 to-blue-500 text-white rounded-lg hover:from-cyan-600 hover:to-blue-600 disabled:opacity-50">
                                    {isSubmitting ? 'Saving...' : '💾 Save Data'}
                                </button>
                            </div>
                        </form>
                    ) : (
                        <div className="p-6">
                            {/* CSV Import Form */}
                            <div className="space-y-6">
                                {/* Format Guide */}
                                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <h4 className="font-semibold text-blue-900 mb-2">📋 CSV Format Guide</h4>
                                    <p className="text-sm text-blue-800 mb-3">Required columns:</p>
                                    <div className="bg-white p-3 rounded text-xs font-mono overflow-x-auto">
                                        Booking ID,Merchant,Transaction Time,Status,Transaction Amount,Description,Sheet
                                    </div>
                                    <p className="text-xs text-blue-700 mt-2">
                                        💡 Enable auto-extract to parse hotel/flight details from Description column
                                    </p>
                                </div>

                                {/* Options */}
                                <div className="space-y-3">
                                    <h4 className="font-semibold">⚙️ Import Options</h4>
                                    <label className="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox" checked={csvOptions.autoPreprocess}
                                            onChange={(e) => setCsvOptions(prev => ({ ...prev, autoPreprocess: e.target.checked }))}
                                            className="w-4 h-4 text-cyan-600 rounded" />
                                        <span className="text-sm">Auto-extract from Description (Hotel name, Route, Employee, etc.)</span>
                                    </label>
                                    <label className="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox" checked={csvOptions.skipSummary}
                                            onChange={(e) => setCsvOptions(prev => ({ ...prev, skipSummary: e.target.checked }))}
                                            className="w-4 h-4 text-cyan-600 rounded" />
                                        <span className="text-sm">Skip summary rows (SUBTOTAL, VAT, TOTAL)</span>
                                    </label>
                                    <label className="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox" checked={csvOptions.forceUpdate}
                                            onChange={(e) => setCsvOptions(prev => ({ ...prev, forceUpdate: e.target.checked }))}
                                            className="w-4 h-4 text-orange-600 rounded" />
                                        <span className="text-sm text-orange-700 font-medium">⚠️ Force update existing records (replace duplicates)</span>
                                    </label>
                                </div>

                                {/* File Upload - Multiple Files */}
                                <div>
                                    <label className="block text-sm font-medium mb-2">Upload CSV atau Excel Files * (Multiple files supported)</label>
                                    <input 
                                        type="file" 
                                        accept=".csv,.xlsx,.xls" 
                                        onChange={handleCSVUpload} 
                                        className="hidden" 
                                        id="csv-upload"
                                        multiple
                                    />
                                    <label htmlFor="csv-upload"
                                        className={`block w-full border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition ${
                                            csvFiles.length > 0 ? 'border-green-400 bg-green-50' : 'border-gray-300 hover:border-cyan-400 hover:bg-cyan-50'
                                        }`}>
                                        <div>
                                            <div className="text-4xl mb-2">{csvFiles.length > 0 ? '✅' : '📤'}</div>
                                            <p className="font-semibold">{csvFiles.length > 0 ? `${csvFiles.length} file(s) selected` : 'Click to upload or drag & drop'}</p>
                                            <p className="text-sm text-gray-500 mt-1">CSV atau Excel files • Multiple files allowed • Auto-convert!</p>
                                        </div>
                                    </label>
                                    {errors.csv && <p className="text-red-500 text-xs mt-1">{errors.csv}</p>}
                                </div>

                                {/* Selected Files List */}
                                {csvFiles.length > 0 && (
                                    <div className="border rounded-lg p-4 bg-gray-50">
                                        <h4 className="font-semibold mb-3 text-sm">📋 Selected Files ({csvFiles.length})</h4>
                                        <div className="space-y-2 max-h-40 overflow-y-auto">
                                            {csvFiles.map((file, index) => (
                                                <div key={index} className="flex items-center justify-between bg-white p-2 rounded border">
                                                    <div className="flex items-center gap-2 flex-1 min-w-0">
                                                        <span className="text-lg">�</span>
                                                        <div className="min-w-0 flex-1">
                                                            <p className="text-sm font-medium truncate">{file.name}</p>
                                                            <p className="text-xs text-gray-500">{(file.size / 1024).toFixed(2)} KB</p>
                                                        </div>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleRemoveFile(index)}
                                                        className="ml-2 text-red-500 hover:text-red-700 p-1"
                                                        title="Remove file"
                                                    >
                                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {/* Preview */}
                                {csvPreview.length > 0 && (
                                    <div>
                                        <h4 className="font-semibold mb-3">👀 Preview (First 5 Rows)</h4>
                                        <div className="border rounded-lg overflow-hidden">
                                            <div className="overflow-x-auto max-h-64">
                                                <table className="min-w-full text-xs">
                                                    <thead className="bg-gray-50">
                                                        <tr>
                                                            {csvPreview[0]?.map((header, idx) => (
                                                                <th key={idx} className="px-3 py-2 text-left font-medium text-gray-700 whitespace-nowrap">
                                                                    {header}
                                                                </th>
                                                            ))}
                                                        </tr>
                                                    </thead>
                                                    <tbody className="bg-white divide-y">
                                                        {csvPreview.slice(1).map((row, idx) => (
                                                            <tr key={idx} className="hover:bg-gray-50">
                                                                {row.map((cell, cellIdx) => (
                                                                    <td key={cellIdx} className="px-3 py-2 text-gray-600 whitespace-nowrap">
                                                                        {cell.length > 50 ? cell.substring(0, 50) + '...' : cell}
                                                                    </td>
                                                                ))}
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Footer */}
                                <div className="flex justify-end gap-3 pt-4 border-t">
                                    <button type="button" onClick={onClose}
                                        className="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100" disabled={isSubmitting}>
                                        Cancel
                                    </button>
                                    <button onClick={handleCSVImport} disabled={isSubmitting || csvFiles.length === 0}
                                        className="px-6 py-2 bg-gradient-to-r from-cyan-500 to-blue-500 text-white rounded-lg hover:from-cyan-600 hover:to-blue-600 disabled:opacity-50">
                                        {isSubmitting ? 'Importing...' : `📤 Import ${csvFiles.length} CSV file${csvFiles.length !== 1 ? 's' : ''}`}
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
