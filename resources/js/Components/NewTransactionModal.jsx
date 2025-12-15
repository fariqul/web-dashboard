import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';

export default function NewTransactionModal({ isOpen, onClose, availableSheets = [] }) {
    const [activeTab, setActiveTab] = useState('manual'); // 'manual', 'import', 'fees'
    const [isSubmitting, setIsSubmitting] = useState(false);
    
    // Manual Entry State
    const [formData, setFormData] = useState({
        employee_name: '',
        personel_number: '',
        trip_number: '',
        origin: '',
        destination: '',
        departure_date: '',
        return_date: '',
        payment_amount: '',
        transaction_type: 'payment',
        sheet: '',
        custom_month: '',
        custom_year: new Date().getFullYear().toString(),
        cc_number: '5657' // Default ke CC 5657
    });
    
    const [employeeSuggestions, setEmployeeSuggestions] = useState([]);
    const [showSuggestions, setShowSuggestions] = useState(false);
    
    // Import CSV State
    const [csvFile, setCSVFile] = useState(null);
    const [updateExisting, setUpdateExisting] = useState(false);
    const [csvPreview, setCSVPreview] = useState(null);
    const [csvSheetName, setCSVSheetName] = useState('');
    
    // Fees State
    const [fees, setFees] = useState([]);
    const [loadingFees, setLoadingFees] = useState(false);
    const [newSheetName, setNewSheetName] = useState('');
    
    if (!isOpen) return null;
    
    // Fetch fees when Fees tab is activated
    const loadFees = async () => {
        if (fees.length > 0) return; // Already loaded
        
        setLoadingFees(true);
        try {
            const response = await axios.get('/cc-card/fees');
            setFees(response.data);
        } catch (error) {
            console.error('Failed to load fees:', error);
        }
        setLoadingFees(false);
    };
    
    // Autocomplete employee name
    const handleEmployeeNameChange = async (value) => {
        setFormData({...formData, employee_name: value});
        
        if (value.length < 2) {
            setEmployeeSuggestions([]);
            setShowSuggestions(false);
            return;
        }
        
        try {
            const response = await axios.get(`/cc-card/transaction/autocomplete?q=${value}`);
            setEmployeeSuggestions(response.data);
            setShowSuggestions(true);
        } catch (error) {
            console.error('Autocomplete error:', error);
        }
    };
    
    const selectEmployee = (employee) => {
        setFormData({
            ...formData,
            employee_name: employee.value,
            personel_number: employee.personel_number
        });
        setShowSuggestions(false);
    };
    
    // Submit manual transaction
    const handleManualSubmit = async (e) => {
        e.preventDefault();
        setIsSubmitting(true);
        
        try {
            const response = await axios.post('/cc-card/transaction/store', formData);
            
            // Success - redirect with sheet parameter
            const sheetName = `${formData.custom_month} ${formData.custom_year}${formData.cc_number ? ' - CC ' + formData.cc_number : ''}`;
            router.get('/cc-card', { sheet: sheetName }, {
                onSuccess: () => {
                    onClose();
                }
            });
        } catch (error) {
            console.error('Error creating transaction:', error);
            if (error.response?.data?.errors) {
                const errorMessages = Object.values(error.response.data.errors).flat().join('\n');
                alert('Validation errors:\n' + errorMessages);
            } else {
                alert('Failed to create transaction: ' + (error.response?.data?.message || error.message));
            }
        } finally {
            setIsSubmitting(false);
        }
    };
    
    // Handle CSV file selection
    const handleCSVChange = (e) => {
        const file = e.target.files[0];
        if (!file) return;
        
        setCSVFile(file);
        
        // Preview first 5 rows
        const reader = new FileReader();
        reader.onload = (event) => {
            const text = event.target.result;
            const lines = text.split('\n').slice(0, 6); // Header + 5 rows
            setCSVPreview(lines);
        };
        reader.readAsText(file);
    };
    
    // Submit CSV import
    const handleCSVImport = (e) => {
        e.preventDefault();
        if (!csvFile) return;
        
        setIsSubmitting(true);
        
        const formData = new FormData();
        formData.append('csv_file', csvFile);
        formData.append('update_existing', updateExisting ? '1' : '0');
        formData.append('override_sheet_name', csvSheetName); // Override sheet name in CSV
        
        router.post('/cc-card/transaction/import', formData, {
            onSuccess: () => {
                onClose();
            },
            onError: (errors) => {
                console.error('Import errors:', errors);
                setIsSubmitting(false);
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    };
    
    // Update fees
    const handleFeesUpdate = async (e) => {
        e.preventDefault();
        setIsSubmitting(true);
        
        try {
            await axios.post('/cc-card/fees/update', { fees });
            alert('Biaya berhasil diupdate!');
            onClose();
        } catch (error) {
            console.error('Failed to update fees:', error);
            alert('Gagal update biaya: ' + error.message);
        }
        
        setIsSubmitting(false);
    };
    
    const updateFeeValue = (index, field, value) => {
        const newFees = [...fees];
        newFees[index][field] = parseFloat(value) || 0;
        setFees(newFees);
    };
    
    const addNewSheet = () => {
        if (!newSheetName.trim()) return;
        
        // Check if sheet already exists
        if (fees.some(f => f.sheet_name === newSheetName.trim())) {
            alert('Sheet dengan nama ini sudah ada!');
            return;
        }
        
        const newSheet = {
            id: 'new_' + Date.now(), // Temporary ID
            sheet_name: newSheetName.trim(),
            biaya_adm_bunga: 0,
            biaya_transfer: 0,
            iuran_tahunan: 0
        };
        
        setFees([...fees, newSheet]);
        setNewSheetName('');
    };
    
    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                {/* Header */}
                <div className="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 className="text-2xl font-bold">New Transaction</h2>
                    <button 
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700 text-2xl font-bold"
                    >
                        ×
                    </button>
                </div>
                
                {/* Tabs */}
                <div className="flex border-b border-gray-200">
                    <button
                        onClick={() => setActiveTab('manual')}
                        className={`px-6 py-3 font-semibold transition ${
                            activeTab === 'manual'
                                ? 'border-b-2 border-cyan-500 text-cyan-600'
                                : 'text-gray-600 hover:text-gray-900'
                        }`}
                    >
                        Manual Entry
                    </button>
                    <button
                        onClick={() => setActiveTab('import')}
                        className={`px-6 py-3 font-semibold transition ${
                            activeTab === 'import'
                                ? 'border-b-2 border-cyan-500 text-cyan-600'
                                : 'text-gray-600 hover:text-gray-900'
                        }`}
                    >
                        Import CSV
                    </button>
                    <button
                        onClick={() => {
                            setActiveTab('fees');
                            loadFees();
                        }}
                        className={`px-6 py-3 font-semibold transition ${
                            activeTab === 'fees'
                                ? 'border-b-2 border-cyan-500 text-cyan-600'
                                : 'text-gray-600 hover:text-gray-900'
                        }`}
                    >
                        Biaya Tambahan
                    </button>
                </div>
                
                {/* Content */}
                <div className="flex-1 overflow-y-auto p-6">
                    {/* Manual Entry Tab */}
                    {activeTab === 'manual' && (
                        <form onSubmit={handleManualSubmit} className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                {/* Employee Name with Autocomplete */}
                                <div className="relative">
                                    <label className="block text-sm font-semibold mb-1">Nama Karyawan *</label>
                                    <input
                                        type="text"
                                        value={formData.employee_name}
                                        onChange={(e) => handleEmployeeNameChange(e.target.value)}
                                        onBlur={() => setTimeout(() => setShowSuggestions(false), 200)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                        required
                                    />
                                    {showSuggestions && employeeSuggestions.length > 0 && (
                                        <div className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                            {employeeSuggestions.map((emp, idx) => (
                                                <button
                                                    key={idx}
                                                    type="button"
                                                    onClick={() => selectEmployee(emp)}
                                                    className="w-full text-left px-3 py-2 hover:bg-cyan-50 text-sm"
                                                >
                                                    {emp.label}
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold mb-1">Personnel Number *</label>
                                    <input
                                        type="text"
                                        value={formData.personel_number}
                                        onChange={(e) => setFormData({...formData, personel_number: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                        required
                                    />
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold mb-1">Trip Number *</label>
                                    <input
                                        type="text"
                                        value={formData.trip_number}
                                        onChange={(e) => setFormData({...formData, trip_number: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                        required
                                    />
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold mb-1">Bulan *</label>
                                    <select
                                        value={formData.custom_month}
                                        onChange={(e) => setFormData({...formData, custom_month: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                        required
                                    >
                                        <option value="">Pilih Bulan</option>
                                        <option value="Januari">Januari</option>
                                        <option value="Februari">Februari</option>
                                        <option value="Maret">Maret</option>
                                        <option value="April">April</option>
                                        <option value="Mei">Mei</option>
                                        <option value="Juni">Juni</option>
                                        <option value="Juli">Juli</option>
                                        <option value="Agustus">Agustus</option>
                                        <option value="September">September</option>
                                        <option value="Oktober">Oktober</option>
                                        <option value="November">November</option>
                                        <option value="Desember">Desember</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold mb-1">Tahun *</label>
                                    <input
                                        type="number"
                                        value={formData.custom_year}
                                        onChange={(e) => setFormData({...formData, custom_year: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                        min="2020"
                                        max="2100"
                                        required
                                    />
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold mb-1">CC Number *</label>
                                    <select
                                        value={formData.cc_number || ''}
                                        onChange={(e) => setFormData({...formData, cc_number: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 bg-white"
                                        required
                                    >
                                        <option value="">-- Pilih CC Number --</option>
                                        <option value="5657">CC 5657</option>
                                        <option value="9386">CC 9386</option>
                                    </select>
                                    <p className="text-xs text-gray-500 mt-1">Wajib pilih nomor kartu kredit</p>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold mb-1">Origin *</label>
                                    <input
                                        type="text"
                                        value={formData.origin}
                                        onChange={(e) => setFormData({...formData, origin: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                        placeholder="Kota Makassar"
                                        required
                                    />
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold mb-1">Destination *</label>
                                    <input
                                        type="text"
                                        value={formData.destination}
                                        onChange={(e) => setFormData({...formData, destination: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                        placeholder="Jakarta Selatan"
                                        required
                                    />
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold mb-1">Departure Date *</label>
                                    <input
                                        type="date"
                                        value={formData.departure_date}
                                        onChange={(e) => setFormData({...formData, departure_date: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                        required
                                    />
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold mb-1">Return Date *</label>
                                    <input
                                        type="date"
                                        value={formData.return_date}
                                        onChange={(e) => setFormData({...formData, return_date: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                        required
                                    />
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold mb-1">Payment Amount *</label>
                                    <input
                                        type="number"
                                        value={formData.payment_amount}
                                        onChange={(e) => setFormData({...formData, payment_amount: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                        placeholder="1000000"
                                        min="0"
                                        step="0.01"
                                        required
                                    />
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold mb-1">Transaction Type *</label>
                                    <select
                                        value={formData.transaction_type}
                                        onChange={(e) => setFormData({...formData, transaction_type: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                        required
                                    >
                                        <option value="payment">Payment</option>
                                        <option value="refund">Refund</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div className="flex justify-end gap-3 pt-4">
                                <button
                                    type="button"
                                    onClick={onClose}
                                    className="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmitting}
                                    className="px-6 py-2 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 transition disabled:opacity-50"
                                >
                                    {isSubmitting ? 'Saving...' : 'Save Transaction'}
                                </button>
                            </div>
                        </form>
                    )}
                    
                    {/* Import CSV Tab */}
                    {activeTab === 'import' && (
                        <form onSubmit={handleCSVImport} className="space-y-4">
                            <div>
                                <label className="block text-sm font-semibold mb-2">Upload CSV atau Excel File *</label>
                                <input
                                    type="file"
                                    accept=".csv,.xlsx,.xls"
                                    onChange={handleCSVChange}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                    required
                                />
                                <p className="text-sm text-gray-600 mt-1">
                                    ✅ Upload CSV atau Excel - Otomatis convert!<br/>
                                    Format: Booking ID, Name, Personnel Number, Trip Number, Trip Destination, Trip Date, Payment, Transaction Type
                                </p>
                            </div>
                            
                            <div>
                                <label className="block text-sm font-semibold mb-2">Override Sheet Name (Opsional)</label>
                                <input
                                    type="text"
                                    value={csvSheetName}
                                    onChange={(e) => setCSVSheetName(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                    placeholder="Contoh: Oktober 2025"
                                />
                                <p className="text-sm text-gray-600 mt-1">
                                    Jika diisi, semua transaksi di CSV akan diimport ke sheet ini (mengabaikan kolom Sheet di CSV)
                                </p>
                            </div>
                            
                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="updateExisting"
                                    checked={updateExisting}
                                    onChange={(e) => setUpdateExisting(e.target.checked)}
                                    className="w-4 h-4 text-cyan-500 rounded focus:ring-2 focus:ring-cyan-500"
                                />
                                <label htmlFor="updateExisting" className="text-sm font-medium">
                                    Update existing transactions (if booking ID matches)
                                </label>
                            </div>
                            
                            {csvPreview && (
                                <div className="bg-gray-100 rounded-lg p-4">
                                    <p className="font-semibold mb-2">Preview (first 5 rows):</p>
                                    <div className="overflow-x-auto">
                                        <pre className="text-xs whitespace-pre-wrap">
                                            {csvPreview.join('\n')}
                                        </pre>
                                    </div>
                                </div>
                            )}
                            
                            <div className="flex justify-end gap-3 pt-4">
                                <button
                                    type="button"
                                    onClick={onClose}
                                    className="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmitting || !csvFile}
                                    className="px-6 py-2 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 transition disabled:opacity-50"
                                >
                                    {isSubmitting ? 'Importing...' : 'Import CSV'}
                                </button>
                            </div>
                        </form>
                    )}
                    
                    {/* Fees Tab */}
                    {activeTab === 'fees' && (
                        <form onSubmit={handleFeesUpdate} className="space-y-4">
                            {loadingFees ? (
                                <div className="text-center py-8">
                                    <p className="text-gray-600">Loading fees...</p>
                                </div>
                            ) : (
                                <>
                                    <div className="space-y-4">
                                        {fees.map((fee, index) => (
                                            <div key={fee.id} className="bg-gray-50 rounded-lg p-4">
                                                <div className="flex justify-between items-center mb-3">
                                                    <h3 className="font-bold">{fee.sheet_name}</h3>
                                                    <button
                                                        type="button"
                                                        onClick={async () => {
                                                            if (confirm(`Yakin ingin menghapus biaya tambahan untuk "${fee.sheet_name}"?`)) {
                                                                try {
                                                                    await axios.delete('/cc-card/fees/delete', {
                                                                        data: { sheet_name: fee.sheet_name }
                                                                    });
                                                                    alert('Biaya berhasil dihapus!');
                                                                    loadFees(); // Reload fees
                                                                } catch (error) {
                                                                    console.error('Delete failed:', error);
                                                                    alert('Gagal menghapus: ' + (error.response?.data?.error || error.message));
                                                                }
                                                            }
                                                        }}
                                                        className="px-3 py-1 text-sm bg-red-500 text-white rounded hover:bg-red-600 transition"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                                <div className="grid grid-cols-3 gap-4">
                                                    <div>
                                                        <label className="block text-sm font-medium mb-1">Biaya ADM & Bunga</label>
                                                        <input
                                                            type="number"
                                                            value={fee.biaya_adm_bunga}
                                                            onChange={(e) => updateFeeValue(index, 'biaya_adm_bunga', e.target.value)}
                                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                                            min="0"
                                                            step="0.01"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label className="block text-sm font-medium mb-1">Biaya Transfer</label>
                                                        <input
                                                            type="number"
                                                            value={fee.biaya_transfer}
                                                            onChange={(e) => updateFeeValue(index, 'biaya_transfer', e.target.value)}
                                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                                            min="0"
                                                            step="0.01"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label className="block text-sm font-medium mb-1">Iuran Tahunan</label>
                                                        <input
                                                            type="number"
                                                            value={fee.iuran_tahunan}
                                                            onChange={(e) => updateFeeValue(index, 'iuran_tahunan', e.target.value)}
                                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                                            min="0"
                                                            step="0.01"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                    
                                    {/* Add New Sheet */}
                                    <div className="border-t pt-4 mt-4">
                                        <h3 className="font-bold mb-3">Tambah Sheet Baru</h3>
                                        <div className="bg-blue-50 rounded-lg p-4">
                                            <div className="mb-3">
                                                <label className="block text-sm font-medium mb-1">Nama Sheet *</label>
                                                <input
                                                    type="text"
                                                    value={newSheetName}
                                                    onChange={(e) => setNewSheetName(e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                                    placeholder="Contoh: Oktober 2025 atau November 2025 - CC 5657"
                                                />
                                                <p className="text-xs text-gray-600 mt-1">
                                                    Format: [Bulan] [Tahun] atau [Bulan] [Tahun] - CC [Number]
                                                </p>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={addNewSheet}
                                                disabled={!newSheetName.trim()}
                                                className="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                ➕ Tambah Sheet
                                            </button>
                                        </div>
                                    </div>
                                </>
                            )}
                            
                            <div className="flex justify-end gap-3 pt-4">
                                <button
                                    type="button"
                                    onClick={onClose}
                                    className="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmitting || loadingFees}
                                    className="px-6 py-2 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 transition disabled:opacity-50"
                                >
                                    {isSubmitting ? 'Updating...' : 'Update Biaya'}
                                </button>
                            </div>
                        </form>
                    )}
                </div>
            </div>
        </div>
    );
}
