import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';

export default function TransactionDetailModal({ isOpen, onClose, transactionId, mode = 'view' }) {
    const [transaction, setTransaction] = useState(null);
    const [isEditing, setIsEditing] = useState(mode === 'edit');
    const [isSubmitting, setIsSubmitting] = useState(false);
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
        custom_month: '',
        custom_year: '',
        cc_number: ''
    });
    
    useEffect(() => {
        if (isOpen && transactionId) {
            loadTransaction();
        }
    }, [isOpen, transactionId]);
    
    const loadTransaction = async () => {
        try {
            const response = await axios.get(`/cc-card/transaction/${transactionId}`);
            const data = response.data;
            setTransaction(data);
            
            // Parse sheet name to extract month, year, cc_number
            // Format: "Oktober 2025" or "Oktober 2025 - CC 5657"
            let month = '';
            let year = '';
            let ccNumber = '';
            
            if (data.sheet.includes(' - CC ')) {
                // Format: "Oktober 2025 - CC 5657"
                const parts = data.sheet.split(' - CC ');
                const monthYear = parts[0].split(' ');
                month = monthYear[0] || '';
                year = monthYear[1] || '';
                ccNumber = parts[1] || '';
            } else {
                // Format: "Oktober 2025"
                const monthYear = data.sheet.split(' ');
                month = monthYear[0] || '';
                year = monthYear[1] || '';
            }
            
            console.log('Parsed sheet:', { month, year, ccNumber, original: data.sheet }); // Debug
            
            setFormData({
                employee_name: data.employee_name,
                personel_number: data.personel_number,
                trip_number: data.trip_number,
                origin: data.origin,
                destination: data.destination,
                departure_date: data.departure_date,
                return_date: data.return_date,
                payment_amount: data.payment_amount,
                transaction_type: data.transaction_type,
                custom_month: month,
                custom_year: year,
                cc_number: ccNumber
            });
        } catch (error) {
            console.error('Failed to load transaction:', error);
            alert('Failed to load transaction details');
        }
    };
    
    const handleUpdate = async (e) => {
        e.preventDefault();
        setIsSubmitting(true);
        
        try {
            console.log('Sending data:', formData); // Debug
            await axios.put(`/cc-card/transaction/${transactionId}`, formData);
            alert('Transaction updated successfully!');
            window.location.reload(); // Refresh to show updated data
        } catch (error) {
            console.error('Update failed:', error);
            console.error('Error response:', error.response?.data); // Debug
            
            if (error.response?.data?.errors) {
                const errors = error.response.data.errors;
                const errorMessages = Object.entries(errors)
                    .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
                    .join('\n');
                alert('Validation errors:\n\n' + errorMessages);
            } else {
                alert('Failed to update transaction: ' + (error.response?.data?.message || error.message));
            }
        }
        
        setIsSubmitting(false);
    };
    
    const handleDelete = async () => {
        if (!confirm('Are you sure you want to delete this transaction? This action cannot be undone.')) {
            return;
        }
        
        setIsSubmitting(true);
        
        try {
            await axios.delete(`/cc-card/transaction/${transactionId}`);
            alert('Transaction deleted successfully!');
            window.location.reload();
        } catch (error) {
            console.error('Delete failed:', error);
            alert('Failed to delete transaction: ' + (error.response?.data?.message || error.message));
            setIsSubmitting(false);
        }
    };
    
    if (!isOpen || !transaction) return null;
    
    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                {/* Header */}
                <div className="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 className="text-2xl font-bold">
                        {isEditing ? 'Edit Transaction' : 'Transaction Details'}
                    </h2>
                    <button 
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700 text-2xl font-bold"
                    >
                        ×
                    </button>
                </div>
                
                {/* Content */}
                <div className="flex-1 overflow-y-auto p-6">
                    {isEditing ? (
                        // Edit Form
                        <form onSubmit={handleUpdate} className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-semibold mb-1">Booking ID</label>
                                    <input
                                        type="text"
                                        value={transaction.booking_id}
                                        disabled
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">Booking ID cannot be changed</p>
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
                                
                                <div>
                                    <label className="block text-sm font-semibold mb-1">Employee Name *</label>
                                    <input
                                        type="text"
                                        value={formData.employee_name}
                                        onChange={(e) => setFormData({...formData, employee_name: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                        required
                                    />
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
                                    <label className="block text-sm font-semibold mb-1">Payment Amount *</label>
                                    <input
                                        type="number"
                                        value={formData.payment_amount}
                                        onChange={(e) => setFormData({...formData, payment_amount: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                        min="0"
                                        step="0.01"
                                        required
                                    />
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold mb-1">Origin *</label>
                                    <input
                                        type="text"
                                        value={formData.origin}
                                        onChange={(e) => setFormData({...formData, origin: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
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
                                    <select
                                        value={formData.custom_year}
                                        onChange={(e) => setFormData({...formData, custom_year: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                        required
                                    >
                                        <option value="">Pilih Tahun</option>
                                        {Array.from({length: 5}, (_, i) => new Date().getFullYear() + i).map(year => (
                                            <option key={year} value={year}>{year}</option>
                                        ))}
                                    </select>
                                </div>
                                
                                <div className="col-span-2">
                                    <label className="block text-sm font-semibold mb-1">CC Number (Optional)</label>
                                    <input
                                        type="text"
                                        value={formData.cc_number}
                                        onChange={(e) => setFormData({...formData, cc_number: e.target.value})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                        placeholder="5657 atau 9386"
                                    />
                                </div>
                            </div>
                            
                            <div className="flex justify-between pt-4">
                                <button
                                    type="button"
                                    onClick={() => setIsEditing(false)}
                                    className="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                                    disabled={isSubmitting}
                                >
                                    Cancel Edit
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmitting}
                                    className="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition disabled:opacity-50"
                                >
                                    {isSubmitting ? 'Updating...' : 'Update Transaction'}
                                </button>
                            </div>
                        </form>
                    ) : (
                        // View Mode
                        <div className="space-y-6">
                            <div className="grid grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-semibold text-gray-600 mb-1">Booking ID</label>
                                    <p className="text-lg font-mono bg-gray-50 px-3 py-2 rounded">{transaction.booking_id}</p>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold text-gray-600 mb-1">Transaction Type</label>
                                    <p className="text-lg">
                                        <span className={`px-3 py-1 rounded-full text-sm font-semibold ${
                                            transaction.transaction_type === 'payment' 
                                                ? 'bg-green-100 text-green-800' 
                                                : 'bg-red-100 text-red-800'
                                        }`}>
                                            {transaction.transaction_type.toUpperCase()}
                                        </span>
                                    </p>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold text-gray-600 mb-1">Employee Name</label>
                                    <p className="text-lg">{transaction.employee_name}</p>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold text-gray-600 mb-1">Personnel Number</label>
                                    <p className="text-lg">{transaction.personel_number}</p>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold text-gray-600 mb-1">Trip Number</label>
                                    <p className="text-lg">{transaction.trip_number}</p>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold text-gray-600 mb-1">Payment Amount</label>
                                    <p className="text-lg font-bold text-green-600">
                                        Rp {Number(transaction.payment_amount).toLocaleString('id-ID')}
                                    </p>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold text-gray-600 mb-1">Route</label>
                                    <p className="text-lg">{transaction.trip_destination_full}</p>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold text-gray-600 mb-1">Sheet/Period</label>
                                    <p className="text-lg font-semibold text-cyan-600">{transaction.sheet}</p>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold text-gray-600 mb-1">Departure Date</label>
                                    <p className="text-lg">{transaction.departure_date}</p>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold text-gray-600 mb-1">Return Date</label>
                                    <p className="text-lg">{transaction.return_date}</p>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold text-gray-600 mb-1">Duration</label>
                                    <p className="text-lg">{transaction.duration_days} days</p>
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-semibold text-gray-600 mb-1">Status</label>
                                    <p className="text-lg">
                                        <span className="px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                                            {transaction.status.toUpperCase()}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            
                            <div className="flex justify-between pt-4 border-t">
                                <button
                                    onClick={handleDelete}
                                    disabled={isSubmitting}
                                    className="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition disabled:opacity-50 font-semibold"
                                >
                                    🗑️ Delete Transaction
                                </button>
                                <div className="flex gap-3">
                                    <button
                                        onClick={onClose}
                                        className="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                                    >
                                        Close
                                    </button>
                                    <button
                                        onClick={() => setIsEditing(true)}
                                        className="px-6 py-2 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 transition font-semibold"
                                    >
                                        ✏️ Edit Transaction
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
