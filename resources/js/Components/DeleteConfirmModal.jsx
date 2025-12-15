import React from 'react';
import { router } from '@inertiajs/react';

export default function DeleteConfirmModal({ isOpen, onClose, data }) {
    if (!isOpen || !data) return null;

    const handleDelete = () => {
        router.delete(`/service-fee/${data.id}`, {
            onSuccess: () => {
                onClose();
                // Let Inertia handle redirect to preserve flash messages
            }
        });
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={onClose}></div>

                <div className="relative bg-white rounded-lg shadow-xl max-w-md w-full">
                    {/* Icon Warning */}
                    <div className="flex items-center justify-center pt-6">
                        <div className="bg-red-100 rounded-full p-3">
                            <svg className="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>

                    {/* Content */}
                    <div className="text-center px-6 py-4">
                        <h3 className="text-2xl font-bold text-gray-900 mb-2">Delete Service Fee?</h3>
                        <p className="text-gray-600 mb-4">
                            Are you sure you want to delete this service fee record? This action cannot be undone.
                        </p>
                        
                        {/* Data Details */}
                        <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                            <div className="text-left space-y-2">
                                <div className="flex justify-between">
                                    <span className="text-sm text-gray-700">Booking ID:</span>
                                    <span className="text-sm font-semibold">{data.booking_id}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-gray-700">Type:</span>
                                    <span className="text-sm font-semibold">
                                        {data.service_type === 'hotel' ? '🏨 Hotel' : '✈️ Flight'}
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-gray-700">Employee:</span>
                                    <span className="text-sm font-semibold">{data.employee_name || '-'}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-gray-700">Amount:</span>
                                    <span className="text-sm font-semibold">
                                        Rp {new Intl.NumberFormat('id-ID').format(data.transaction_amount)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="px-6 py-4 bg-gray-50 rounded-b-lg flex gap-3">
                        <button onClick={onClose}
                            className="flex-1 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition">
                            Cancel
                        </button>
                        <button onClick={handleDelete}
                            className="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            🗑️ Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
