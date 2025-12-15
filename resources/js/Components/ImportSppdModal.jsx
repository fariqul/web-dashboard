import React, { useState } from 'react';
import axios from 'axios';
import toast from 'react-hot-toast';

export default function ImportSppdModal({ isOpen, onClose }) {
    const [file, setFile] = useState(null);
    const [isUploading, setIsUploading] = useState(false);
    const [dragActive, setDragActive] = useState(false);
    const [sheetMonth, setSheetMonth] = useState('');
    const [sheetYear, setSheetYear] = useState(new Date().getFullYear().toString());

    const handleDrag = (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (e.type === "dragenter" || e.type === "dragover") {
            setDragActive(true);
        } else if (e.type === "dragleave") {
            setDragActive(false);
        }
    };

    const handleDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);
        
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            setFile(e.dataTransfer.files[0]);
        }
    };

    const handleFileChange = (e) => {
        if (e.target.files && e.target.files[0]) {
            setFile(e.target.files[0]);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        if (!file) {
            toast.error('Please select a CSV or Excel file');
            return;
        }

        const formData = new FormData();
        formData.append('csv_file', file);
        if (sheetMonth) formData.append('sheet_month', sheetMonth);
        if (sheetYear) formData.append('sheet_year', sheetYear);

        setIsUploading(true);
        
        try {
            const response = await axios.post('/sppd/transaction/import', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            toast.success(`Successfully imported ${response.data.imported} SPPD trips!`);
            setFile(null);
            onClose();
            
            // Reload page to show new data
            window.location.reload();
        } catch (error) {
            console.error('Import error:', error);
            toast.error(error.response?.data?.message || 'Failed to import CSV file');
        } finally {
            setIsUploading(false);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                {/* Header */}
                <div className="bg-gradient-to-r from-purple-600 via-purple-500 to-pink-500 text-white p-6 rounded-t-2xl">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-2xl font-bold">Import SPPD Data</h2>
                            <p className="text-purple-100 text-sm mt-1">Upload CSV file to import trip data</p>
                        </div>
                        <button
                            onClick={onClose}
                            className="p-2 hover:bg-white/20 rounded-lg transition"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {/* Body */}
                <form onSubmit={handleSubmit} className="p-6">
                    {/* File Upload Area */}
                    <div
                        className={`border-2 border-dashed rounded-xl p-8 text-center transition ${
                            dragActive 
                                ? 'border-purple-500 bg-purple-50' 
                                : 'border-gray-300 hover:border-purple-400'
                        }`}
                        onDragEnter={handleDrag}
                        onDragLeave={handleDrag}
                        onDragOver={handleDrag}
                        onDrop={handleDrop}
                    >
                        <div className="flex flex-col items-center">
                            <svg className="w-16 h-16 text-purple-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            
                            {file ? (
                                <div className="mb-4">
                                    <p className="text-lg font-semibold text-gray-900 mb-2">Selected File:</p>
                                    <div className="flex items-center gap-2 bg-purple-50 px-4 py-2 rounded-lg">
                                        <svg className="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span className="text-purple-900 font-medium">{file.name}</span>
                                        <button
                                            type="button"
                                            onClick={() => setFile(null)}
                                            className="ml-2 text-purple-600 hover:text-purple-800"
                                        >
                                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            ) : (
                                <>
                                    <p className="text-lg font-semibold text-gray-900 mb-2">
                                        Drag and drop your CSV or Excel file here
                                    </p>
                                    <p className="text-sm text-gray-500 mb-4">or</p>
                                </>
                            )}
                            
                            <label className="cursor-pointer">
                                <span className="px-6 py-3 bg-gradient-to-r from-blue-500 to-cyan-500 text-white rounded-lg font-semibold hover:from-blue-600 hover:to-cyan-600 transition inline-block">
                                    Choose File
                                </span>
                                <input
                                    type="file"
                                    accept=".csv,.xlsx,.xls"
                                    onChange={handleFileChange}
                                    className="hidden"
                                />
                            </label>
                        </div>
                    </div>

                    {/* Sheet Month/Year Selection */}
                    <div className="mt-6 bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-lg p-5">
                        <h3 className="font-semibold text-purple-900 mb-3 flex items-center gap-2">
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Sheet Period (Optional)
                        </h3>
                        <p className="text-sm text-purple-700 mb-3">
                            Select the month and year for this import. Leave blank to auto-detect from trip dates.
                        </p>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-purple-900 mb-2">Month</label>
                                <select
                                    value={sheetMonth}
                                    onChange={(e) => setSheetMonth(e.target.value)}
                                    className="w-full px-4 py-2 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                >
                                    <option value="">Auto-detect</option>
                                    <option value="1">January</option>
                                    <option value="2">February</option>
                                    <option value="3">March</option>
                                    <option value="4">April</option>
                                    <option value="5">May</option>
                                    <option value="6">June</option>
                                    <option value="7">July</option>
                                    <option value="8">August</option>
                                    <option value="9">September</option>
                                    <option value="10">October</option>
                                    <option value="11">November</option>
                                    <option value="12">December</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-purple-900 mb-2">Year</label>
                                <input
                                    type="number"
                                    value={sheetYear}
                                    onChange={(e) => setSheetYear(e.target.value)}
                                    min="2020"
                                    max="2100"
                                    placeholder="2025"
                                    className="w-full px-4 py-2 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Format Info */}
                    <div className="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 className="font-semibold text-blue-900 mb-2 flex items-center gap-2">
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Supported Formats
                        </h3>
                        <div className="text-sm text-blue-800 space-y-2">
                            <div>
                                <p className="font-semibold">📄 CSV Format:</p>
                                <p className="ml-4">trip_number, customer_name, trip_destination, reason_for_trip, trip_begins_on, trip_ends_on, paid_amount, beneficiary_bank_name</p>
                            </div>
                            <div>
                                <p className="font-semibold">📊 Excel Format (Auto-Convert):</p>
                                <p className="ml-4">Upload Excel file from SAP export - will be automatically converted to the correct CSV format</p>
                            </div>
                        </div>
                    </div>

                    {/* Buttons */}
                    <div className="flex gap-3 mt-6">
                        <button
                            type="button"
                            onClick={onClose}
                            className="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={!file || isUploading}
                            className="flex-1 px-6 py-3 bg-gradient-to-r from-blue-500 to-cyan-500 text-white rounded-lg font-semibold hover:from-blue-600 hover:to-cyan-600 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            {isUploading ? (
                                <>
                                    <svg className="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Importing...
                                </>
                            ) : (
                                <>
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Import File
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
