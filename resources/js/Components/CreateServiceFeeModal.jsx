import React, { useState } from 'react';
import { router } from '@inertiajs/react';

export default function CreateServiceFeeModal({ isOpen, onClose, availableSheets = [] }) {
    const [activeTab, setActiveTab] = useState('manual'); // 'manual' or 'csv'
    const [serviceType, setServiceType] = useState('hotel'); // 'hotel' or 'flight'
    const [errors, setErrors] = useState({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Manual Entry Form State
    const [formData, setFormData] = useState({
        booking_id: '',
        transaction_time: '',
        sheet: '',
        status: 'settlement',
        transaction_amount: '',
        // Hotel fields
        hotel_name: '',
        room_type: '',
        employee_name: '',
        // Flight fields
        route: '',
        trip_type: 'One Way',
        pax: '',
        airline_id: '',
        booker_email: ''
    });

    // CSV Import State
    const [csvFile, setCSVFile] = useState(null);
    const [csvPreview, setCSVPreview] = useState([]);
    const [csvOptions, setCsvOptions] = useState({
        autoPreprocess: true,
        skipSummary: true
    });
    const [importProgress, setImportProgress] = useState(0);

    if (!isOpen) return null;

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
        
        // Clear error for this field
        if (errors[name]) {
            setErrors(prev => ({ ...prev, [name]: null }));
        }
    };

    const calculateServiceFee = (amount) => {
        const transAmount = parseFloat(amount) || 0;
        // Service fee is 2.5% of transaction amount
        return Math.floor(transAmount * 0.025);
    };

    const handleManualSubmit = async (e) => {
        e.preventDefault();
        setIsSubmitting(true);
        setErrors({});

        const serviceFee = calculateServiceFee(formData.transaction_amount);
        const vat = Math.floor(serviceFee * 0.11);

        const submitData = {
            ...formData,
            service_type: serviceType,
            base_amount: serviceFee,
            service_fee: serviceFee,
            vat: vat,
            total_tagihan: serviceFee + vat
        };

        router.post('/service-fee/store', submitData, {
            onSuccess: () => {
                resetForm();
                onClose();
            },
            onError: (errors) => {
                setErrors(errors);
                setIsSubmitting(false);
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    };

    const handleCSVUpload = (e) => {
        const file = e.target.files[0];
        if (!file) return;

        if (!file.name.endsWith('.csv')) {
            setErrors({ csv: 'Please upload a CSV file' });
            return;
        }

        setCSVFile(file);
        
        // Read and preview first 5 rows
        const reader = new FileReader();
        reader.onload = (event) => {
            const text = event.target.result;
            const rows = text.split('\n').slice(0, 6); // Header + 5 rows
            const preview = rows.map(row => row.split(','));
            setCSVPreview(preview);
        };
        reader.readAsText(file);
    };

    const handleCSVImport = async (e) => {
        e.preventDefault();
        if (!csvFile) {
            setErrors({ csv: 'Please select a CSV file' });
            return;
        }

        setIsSubmitting(true);
        setImportProgress(0);

        const formData = new FormData();
        formData.append('csv_file', csvFile);
        formData.append('auto_preprocess', csvOptions.autoPreprocess ? '1' : '0');
        formData.append('skip_summary', csvOptions.skipSummary ? '1' : '0');

        router.post('/service-fee/import-csv', formData, {
            onProgress: (progress) => {
                setImportProgress(progress.percentage);
            },
            onSuccess: () => {
                resetForm();
                onClose();
            },
            onError: (errors) => {
                setErrors(errors);
                setIsSubmitting(false);
            },
            onFinish: () => {
                setIsSubmitting(false);
                setImportProgress(0);
            }
        });
    };

    const resetForm = () => {
        setFormData({
            booking_id: '',
            transaction_time: '',
            sheet: '',
            status: 'settlement',
            transaction_amount: '',
            hotel_name: '',
            room_type: '',
            employee_name: '',
            route: '',
            trip_type: 'One Way',
            pax: '',
            airline_id: '',
            booker_email: ''
        });
        setCSVFile(null);
        setCSVPreview([]);
        setErrors({});
        setServiceType('hotel');
        setActiveTab('manual');
    };

    const handleClose = () => {
        resetForm();
        onClose();
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                {/* Overlay */}
                <div className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onClick={handleClose}></div>

                {/* Modal */}
                <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    {/* Header */}
                    <div className="bg-gradient-to-r from-cyan-500 to-blue-500 px-6 py-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-xl font-bold text-white">
                                ➕ Add New Service Fee Data
                            </h3>
                            <button
                                onClick={handleClose}
                                className="text-white hover:text-gray-200 transition"
                            >
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {/* Tab Navigation */}
                    <div className="border-b border-gray-200 bg-gray-50">
                        <nav className="flex -mb-px">
                            <button
                                onClick={() => setActiveTab('manual')}
                                className={`w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm transition ${
                                    activeTab === 'manual'
                                        ? 'border-cyan-500 text-cyan-600 bg-white'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                }`}
                            >
                                ✍️ Manual Entry
                            </button>
                            <button
                                onClick={() => setActiveTab('csv')}
                                className={`w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm transition ${
                                    activeTab === 'csv'
                                        ? 'border-cyan-500 text-cyan-600 bg-white'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                }`}
                            >
                                📄 Import CSV
                            </button>
                        </nav>
                    </div>

                    {/* Tab Content */}
                    <div className="px-6 py-6 max-h-[70vh] overflow-y-auto">
                        {activeTab === 'manual' ? (
                            <ManualEntryForm
                                serviceType={serviceType}
                                setServiceType={setServiceType}
                                formData={formData}
                                handleInputChange={handleInputChange}
                                errors={errors}
                                availableSheets={availableSheets}
                                calculateServiceFee={calculateServiceFee}
                            />
                        ) : (
                            <CSVImportForm
                                csvFile={csvFile}
                                handleCSVUpload={handleCSVUpload}
                                csvPreview={csvPreview}
                                csvOptions={csvOptions}
                                setCsvOptions={setCsvOptions}
                                errors={errors}
                                importProgress={importProgress}
                            />
                        )}
                    </div>

                    {/* Footer */}
                    <div className="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                        <button
                            onClick={handleClose}
                            className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition"
                            disabled={isSubmitting}
                        >
                            Cancel
                        </button>
                        <button
                            onClick={activeTab === 'manual' ? handleManualSubmit : handleCSVImport}
                            disabled={isSubmitting}
                            className="px-6 py-2 bg-gradient-to-r from-cyan-500 to-blue-500 text-white rounded-lg hover:from-cyan-600 hover:to-blue-600 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {isSubmitting ? (
                                <span className="flex items-center gap-2">
                                    <svg className="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Processing...
                                </span>
                            ) : (
                                activeTab === 'manual' ? '💾 Save Data' : '📤 Import CSV'
                            )}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

function ManualEntryForm({ serviceType, setServiceType, formData, handleInputChange, errors, availableSheets, calculateServiceFee }) {
    const serviceFee = calculateServiceFee(formData.transaction_amount);
    const vat = Math.floor(serviceFee * 0.11);
    const totalTagihan = serviceFee + vat;

    return (
        <form className="space-y-6">
            {/* Service Type Selector */}
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-3">Service Type *</label>
                <div className="flex gap-4">
                    <button
                        type="button"
                        onClick={() => setServiceType('hotel')}
                        className={`flex-1 py-4 px-6 rounded-lg border-2 transition ${
                            serviceType === 'hotel'
                                ? 'border-blue-500 bg-blue-50 text-blue-700'
                                : 'border-gray-200 hover:border-gray-300'
                        }`}
                    >
                        <div className="text-3xl mb-2">🏨</div>
                        <div className="font-semibold">Hotel</div>
                    </button>
                    <button
                        type="button"
                        onClick={() => setServiceType('flight')}
                        className={`flex-1 py-4 px-6 rounded-lg border-2 transition ${
                            serviceType === 'flight'
                                ? 'border-green-500 bg-green-50 text-green-700'
                                : 'border-gray-200 hover:border-gray-300'
                        }`}
                    >
                        <div className="text-3xl mb-2">✈️</div>
                        <div className="font-semibold">Flight</div>
                    </button>
                </div>
            </div>

            {/* Common Fields */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Booking ID *</label>
                    <input
                        type="text"
                        name="booking_id"
                        value={formData.booking_id}
                        onChange={handleInputChange}
                        placeholder="e.g., HL123456789"
                        autoComplete="off"
                        className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-300 ${
                            errors.booking_id ? 'border-red-500' : 'border-gray-300'
                        }`}
                    />
                    {errors.booking_id && <p className="text-red-500 text-xs mt-1">{errors.booking_id}</p>}
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Transaction Date & Time *</label>
                    <input
                        type="datetime-local"
                        name="transaction_time"
                        value={formData.transaction_time}
                        onChange={handleInputChange}
                        className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-300 ${
                            errors.transaction_time ? 'border-red-500' : 'border-gray-300'
                        }`}
                    />
                    {errors.transaction_time && <p className="text-red-500 text-xs mt-1">{errors.transaction_time}</p>}
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Sheet/Month *</label>
                    <select
                        name="sheet"
                        value={formData.sheet}
                        onChange={handleInputChange}
                        className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-300 ${
                            errors.sheet ? 'border-red-500' : 'border-gray-300'
                        }`}
                    >
                        <option value="">Select Month</option>
                        {availableSheets?.map(sheet => (
                            <option key={sheet} value={sheet}>{sheet}</option>
                        ))}
                    </select>
                    {errors.sheet && <p className="text-red-500 text-xs mt-1">{errors.sheet}</p>}
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Transaction Amount *</label>
                    <input
                        type="number"
                        name="transaction_amount"
                        value={formData.transaction_amount}
                        onChange={handleInputChange}
                        placeholder="e.g., 1000000"
                        className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-300 ${
                            errors.transaction_amount ? 'border-red-500' : 'border-gray-300'
                        }`}
                    />
                    {errors.transaction_amount && <p className="text-red-500 text-xs mt-1">{errors.transaction_amount}</p>}
                </div>
            </div>

            {/* Hotel Specific Fields */}
            {serviceType === 'hotel' && (
                <div className="space-y-4 p-4 bg-blue-50 rounded-lg">
                    <h4 className="font-semibold text-blue-900">🏨 Hotel Information</h4>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Hotel Name *</label>
                            <input
                                type="text"
                                name="hotel_name"
                                value={formData.hotel_name}
                                onChange={handleInputChange}
                                placeholder="e.g., Amaris Hotel Smart"
                                className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-300 ${
                                    errors.hotel_name ? 'border-red-500' : 'border-gray-300'
                                }`}
                            />
                            {errors.hotel_name && <p className="text-red-500 text-xs mt-1">{errors.hotel_name}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Room Type *</label>
                            <input
                                type="text"
                                name="room_type"
                                value={formData.room_type}
                                onChange={handleInputChange}
                                placeholder="e.g., Queen 2"
                                className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-300 ${
                                    errors.room_type ? 'border-red-500' : 'border-gray-300'
                                }`}
                            />
                            {errors.room_type && <p className="text-red-500 text-xs mt-1">{errors.room_type}</p>}
                        </div>

                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-2">Employee Name *</label>
                            <input
                                type="text"
                                name="employee_name"
                                value={formData.employee_name}
                                onChange={handleInputChange}
                                placeholder="e.g., ANDI"
                                className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-300 ${
                                    errors.employee_name ? 'border-red-500' : 'border-gray-300'
                                }`}
                            />
                            {errors.employee_name && <p className="text-red-500 text-xs mt-1">{errors.employee_name}</p>}
                        </div>
                    </div>
                </div>
            )}

            {/* Flight Specific Fields */}
            {serviceType === 'flight' && (
                <div className="space-y-4 p-4 bg-green-50 rounded-lg">
                    <h4 className="font-semibold text-green-900">✈️ Flight Information</h4>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Route *</label>
                            <input
                                type="text"
                                name="route"
                                value={formData.route}
                                onChange={handleInputChange}
                                placeholder="e.g., CGK_DPS"
                                className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-300 ${
                                    errors.route ? 'border-red-500' : 'border-gray-300'
                                }`}
                            />
                            {errors.route && <p className="text-red-500 text-xs mt-1">{errors.route}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Trip Type *</label>
                            <select
                                name="trip_type"
                                value={formData.trip_type}
                                onChange={handleInputChange}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-300"
                            >
                                <option value="One Way">One Way</option>
                                <option value="Return">Return</option>
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Pax *</label>
                            <input
                                type="number"
                                name="pax"
                                value={formData.pax}
                                onChange={handleInputChange}
                                placeholder="e.g., 1"
                                className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-300 ${
                                    errors.pax ? 'border-red-500' : 'border-gray-300'
                                }`}
                            />
                            {errors.pax && <p className="text-red-500 text-xs mt-1">{errors.pax}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Airline ID *</label>
                            <input
                                type="text"
                                name="airline_id"
                                value={formData.airline_id}
                                onChange={handleInputChange}
                                placeholder="e.g., GA"
                                className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-300 ${
                                    errors.airline_id ? 'border-red-500' : 'border-gray-300'
                                }`}
                            />
                            {errors.airline_id && <p className="text-red-500 text-xs mt-1">{errors.airline_id}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Booker Email</label>
                            <input
                                type="email"
                                name="booker_email"
                                value={formData.booker_email}
                                onChange={handleInputChange}
                                placeholder="e.g., booker@example.com"
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-300"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Employee Name *</label>
                            <input
                                type="text"
                                name="employee_name"
                                value={formData.employee_name}
                                onChange={handleInputChange}
                                placeholder="e.g., BUDI"
                                className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-300 ${
                                    errors.employee_name ? 'border-red-500' : 'border-gray-300'
                                }`}
                            />
                            {errors.employee_name && <p className="text-red-500 text-xs mt-1">{errors.employee_name}</p>}
                        </div>
                    </div>
                </div>
            )}

            {/* Auto-calculated Summary */}
            {formData.transaction_amount && (
                <div className="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h4 className="font-semibold text-gray-900 mb-3">💰 Calculated Fees</h4>
                    <div className="space-y-2 text-sm">
                        <div className="flex justify-between">
                            <span className="text-gray-600">Service Fee (2.5%):</span>
                            <span className="font-semibold">Rp {serviceFee.toLocaleString('id-ID')}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-gray-600">VAT (11%):</span>
                            <span className="font-semibold">Rp {vat.toLocaleString('id-ID')}</span>
                        </div>
                        <hr className="border-gray-300" />
                        <div className="flex justify-between text-base">
                            <span className="font-bold text-gray-900">Total Tagihan:</span>
                            <span className="font-bold text-cyan-600">Rp {totalTagihan.toLocaleString('id-ID')}</span>
                        </div>
                    </div>
                </div>
            )}
        </form>
    );
}

function CSVImportForm({ csvFile, handleCSVUpload, csvPreview, csvOptions, setCsvOptions, errors, importProgress }) {
    return (
        <div className="space-y-6">
            {/* Format Guide */}
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 className="font-semibold text-blue-900 mb-2">📋 CSV Format Guide</h4>
                <p className="text-sm text-blue-800 mb-3">Your CSV file should contain these columns:</p>
                <div className="bg-white p-3 rounded text-xs font-mono overflow-x-auto">
                    <div className="text-gray-600">Booking ID,Merchant,Transaction Time,Status,Transaction Amount,Description,Sheet</div>
                </div>
                <p className="text-xs text-blue-700 mt-2">
                    💡 Enable "Auto-extract from Description" to automatically parse hotel/flight details from the Description column.
                </p>
            </div>

            {/* Preprocessing Options */}
            <div className="space-y-3">
                <h4 className="font-semibold text-gray-900">⚙️ Import Options</h4>
                <label className="flex items-center gap-3 cursor-pointer">
                    <input
                        type="checkbox"
                        checked={csvOptions.autoPreprocess}
                        onChange={(e) => setCsvOptions(prev => ({ ...prev, autoPreprocess: e.target.checked }))}
                        className="w-5 h-5 text-cyan-600 rounded focus:ring-2 focus:ring-cyan-300"
                    />
                    <span className="text-sm text-gray-700">Auto-extract from Description field (Hotel name, Route, Employee, etc.)</span>
                </label>
                <label className="flex items-center gap-3 cursor-pointer">
                    <input
                        type="checkbox"
                        checked={csvOptions.skipSummary}
                        onChange={(e) => setCsvOptions(prev => ({ ...prev, skipSummary: e.target.checked }))}
                        className="w-5 h-5 text-cyan-600 rounded focus:ring-2 focus:ring-cyan-300"
                    />
                    <span className="text-sm text-gray-700">Skip summary rows (SUBTOTAL, VAT, TOTAL)</span>
                </label>
            </div>

            {/* File Upload */}
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Upload CSV File *</label>
                <div className="relative">
                    <input
                        type="file"
                        accept=".csv"
                        onChange={handleCSVUpload}
                        className="hidden"
                        id="csv-upload"
                    />
                    <label
                        htmlFor="csv-upload"
                        className={`block w-full border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition ${
                            csvFile 
                                ? 'border-green-400 bg-green-50' 
                                : 'border-gray-300 hover:border-cyan-400 hover:bg-cyan-50'
                        }`}
                    >
                        {csvFile ? (
                            <div>
                                <div className="text-4xl mb-2">✅</div>
                                <p className="font-semibold text-green-700">{csvFile.name}</p>
                                <p className="text-sm text-gray-600 mt-1">{(csvFile.size / 1024).toFixed(2)} KB</p>
                                <p className="text-xs text-gray-500 mt-2">Click to change file</p>
                            </div>
                        ) : (
                            <div>
                                <div className="text-4xl mb-2">📤</div>
                                <p className="font-semibold text-gray-700">Click to upload or drag & drop</p>
                                <p className="text-sm text-gray-500 mt-1">CSV file only</p>
                            </div>
                        )}
                    </label>
                </div>
                {errors.csv && <p className="text-red-500 text-xs mt-1">{errors.csv}</p>}
            </div>

            {/* Preview Table */}
            {csvPreview.length > 0 && (
                <div>
                    <h4 className="font-semibold text-gray-900 mb-3">👀 Preview (First 5 Rows)</h4>
                    <div className="border border-gray-200 rounded-lg overflow-hidden">
                        <div className="overflow-x-auto max-h-64">
                            <table className="min-w-full divide-y divide-gray-200 text-xs">
                                <thead className="bg-gray-50">
                                    <tr>
                                        {csvPreview[0]?.map((header, idx) => (
                                            <th key={idx} className="px-3 py-2 text-left font-medium text-gray-700 whitespace-nowrap">
                                                {header}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
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

            {/* Import Progress */}
            {importProgress > 0 && (
                <div>
                    <div className="flex justify-between text-sm mb-2">
                        <span className="font-medium text-gray-700">Importing...</span>
                        <span className="font-semibold text-cyan-600">{importProgress}%</span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-2">
                        <div 
                            className="bg-gradient-to-r from-cyan-500 to-blue-500 h-2 rounded-full transition-all duration-300"
                            style={{ width: `${importProgress}%` }}
                        ></div>
                    </div>
                </div>
            )}
        </div>
    );
}
