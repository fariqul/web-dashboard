import React from 'react';
import { router } from '@inertiajs/react';

export default function ViewServiceFeeModal({ isOpen, onClose, data }) {
    if (!isOpen || !data) return null;

    // Debug log
    console.log('ViewServiceFeeModal data:', data);

    const formatCurrency = (amount) => {
        const numAmount = parseFloat(amount);
        if (isNaN(numAmount) || numAmount === null || numAmount === undefined) {
            return 'Rp 0';
        }
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(numAmount);
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={onClose}></div>

                <div className="relative bg-white rounded-lg shadow-xl max-w-2xl w-full">
                    {/* Header */}
                    <div className="bg-gradient-to-r from-blue-500 to-cyan-500 px-6 py-4 rounded-t-lg">
                        <div className="flex items-center justify-between">
                            <h3 className="text-xl font-bold text-white">
                                {data.service_type === 'hotel' ? '🏨' : '✈️'} Service Fee Detail
                            </h3>
                            <button onClick={onClose} className="text-white hover:text-gray-200">
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {/* Body */}
                    <div className="p-6 space-y-6">
                        {/* Basic Info */}
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <p className="text-sm text-gray-600 mb-1">Booking ID</p>
                                <p className="font-semibold text-lg">{data.booking_id}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 mb-1">Status</p>
                                <span className={`inline-flex px-3 py-1 rounded-full text-sm font-medium ${
                                    data.status === 'settlement' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                }`}>
                                    {data.status.toUpperCase()}
                                </span>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 mb-1">Transaction Time</p>
                                <p className="font-medium">{new Date(data.transaction_time).toLocaleString('id-ID')}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 mb-1">Sheet</p>
                                <p className="font-medium">{data.sheet}</p>
                            </div>
                        </div>

                        {/* Service Details */}
                        {data.service_type === 'hotel' ? (
                            <div className="bg-blue-50 rounded-lg p-4 border border-blue-200">
                                <h4 className="font-semibold text-blue-900 mb-3">🏨 Hotel Information</h4>
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <p className="text-sm text-blue-700">Hotel Name</p>
                                        <p className="font-medium text-gray-900">{data.hotel_name || '-'}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-blue-700">Room Type</p>
                                        <p className="font-medium text-gray-900">{data.room_type || '-'}</p>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div className="bg-green-50 rounded-lg p-4 border border-green-200">
                                <h4 className="font-semibold text-green-900 mb-3">✈️ Flight Information</h4>
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <p className="text-sm text-green-700">Route</p>
                                        <p className="font-medium text-gray-900">{data.route || '-'}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-green-700">Trip Type</p>
                                        <p className="font-medium text-gray-900">{data.trip_type || '-'}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-green-700">Passengers</p>
                                        <p className="font-medium text-gray-900">{data.pax || '-'} pax</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-green-700">Airline</p>
                                        <p className="font-medium text-gray-900">{data.airline_id || '-'}</p>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Employee Info */}
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Employee Name</p>
                            <p className="font-medium">{data.employee_name || '-'}</p>
                        </div>

                        {/* Financial Summary */}
                        <div className="bg-gradient-to-br from-cyan-50 to-blue-50 rounded-lg p-4 border-l-4 border-cyan-500">
                            <h4 className="font-semibold text-gray-900 mb-3">💰 Financial Details</h4>
                            <div className="space-y-2">
                                <div className="flex justify-between">
                                    <span className="text-gray-700">Transaction Amount</span>
                                    <span className="font-semibold">{formatCurrency(data.transaction_amount)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-700">Service Fee (1%)</span>
                                    <span className="font-semibold text-blue-600">{formatCurrency(data.service_fee || data.base_amount)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-700">VAT (11%)</span>
                                    <span className="font-semibold">{formatCurrency(data.vat)}</span>
                                </div>
                                <div className="flex justify-between pt-2 border-t-2 border-cyan-300">
                                    <span className="font-bold text-lg">Total Tagihan</span>
                                    <span className="font-bold text-xl text-cyan-600">{formatCurrency(data.total_tagihan)}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-end">
                        <button onClick={onClose}
                            className="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
