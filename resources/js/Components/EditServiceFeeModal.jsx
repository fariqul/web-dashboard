import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';

export default function EditServiceFeeModal({ isOpen, onClose, data, availableSheets }) {
    const [serviceType, setServiceType] = useState('hotel');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState({});
    const [formData, setFormData] = useState({
        booking_id: '',
        transaction_time: '',
        sheet: '',
        transaction_amount: '',
        employee_name: '',
        status: 'settlement',
        hotel_name: '',
        room_type: '',
        route: '',
        trip_type: 'One Way',
        pax: '1',
        airline_id: '',
        booker_email: ''
    });

    useEffect(() => {
        if (data && isOpen) {
            setServiceType(data.service_type);
            setFormData({
                booking_id: data.booking_id || '',
                transaction_time: data.transaction_time ? new Date(data.transaction_time).toISOString().slice(0, 16) : '',
                sheet: data.sheet || '',
                transaction_amount: data.transaction_amount || '',
                employee_name: data.employee_name || '',
                status: data.status || 'settlement',
                hotel_name: data.hotel_name || '',
                room_type: data.room_type || '',
                route: data.route || '',
                trip_type: data.trip_type || 'One Way',
                pax: data.pax || '1',
                airline_id: data.airline_id || '',
                booker_email: data.booker_email || ''
            });
        }
    }, [data, isOpen]);

    if (!isOpen || !data) return null;

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

        router.put(`/service-fee/${data.id}`, {
            ...formData,
            service_type: serviceType,
            base_amount: fees.serviceFee,
            service_fee: fees.serviceFee,
            vat: fees.vat,
            total_tagihan: fees.total
        }, {
            onSuccess: () => {
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

    const fees = calculateFees(formData.transaction_amount);

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={onClose}></div>

                <div className="relative bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                    {/* Header */}
                    <div className="bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-4 sticky top-0 z-10">
                        <div className="flex items-center justify-between">
                            <h3 className="text-xl font-bold text-white">✏️ Edit Service Fee Data</h3>
                            <button onClick={onClose} className="text-white hover:text-gray-200">
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} className="p-6">
                        {/* Service Type (Disabled - Can't Change) */}
                        <div className="mb-6 bg-gray-50 p-4 rounded-lg border">
                            <label className="block text-sm font-medium mb-2">Service Type (Cannot be changed)</label>
                            <div className="flex gap-4">
                                <div className={`flex-1 py-3 px-4 rounded-lg border-2 ${serviceType === 'hotel' ? 'border-blue-500 bg-blue-50' : 'border-gray-300 opacity-50'}`}>
                                    <div className="text-2xl mb-1">🏨</div>
                                    <div className="font-semibold">Hotel</div>
                                </div>
                                <div className={`flex-1 py-3 px-4 rounded-lg border-2 ${serviceType === 'flight' ? 'border-green-500 bg-green-50' : 'border-gray-300 opacity-50'}`}>
                                    <div className="text-2xl mb-1">✈️</div>
                                    <div className="font-semibold">Flight</div>
                                </div>
                            </div>
                        </div>

                        {/* Common Fields */}
                        <div className="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label className="block text-sm font-medium mb-1">Booking ID *</label>
                                <input type="text" name="booking_id" value={formData.booking_id} onChange={handleInputChange}
                                    className={`w-full px-3 py-2 border rounded-lg ${errors.booking_id ? 'border-red-500' : 'border-gray-300'}`}
                                    required />
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
                                        <option value="auto">🤖 Auto (from Transaction Date)</option>
                                        <optgroup label="Existing Sheets">
                                            {availableSheets?.map(sheet => <option key={sheet} value={sheet}>{sheet}</option>)}
                                        </optgroup>
                                    </select>
                                    <svg className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" 
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-1">Transaction Amount *</label>
                                <input type="number" name="transaction_amount" value={formData.transaction_amount} onChange={handleInputChange}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg" required />
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-1">Status *</label>
                                <select name="status" value={formData.status} onChange={handleInputChange}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
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
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="e.g., CGK_UPG" required />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium mb-1">Trip Type *</label>
                                        <select name="trip_type" value={formData.trip_type} onChange={handleInputChange}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                                            <option value="One Way">One Way</option>
                                            <option value="Return">Return</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium mb-1">Passengers *</label>
                                        <input type="number" name="pax" value={formData.pax} onChange={handleInputChange}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg" min="1" required />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium mb-1">Airline ID *</label>
                                        <input type="text" name="airline_id" value={formData.airline_id} onChange={handleInputChange}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="e.g., GA, JT" required />
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

                        {/* Fee Preview */}
                        {formData.transaction_amount > 0 && (
                            <div className="bg-gray-100 rounded-lg p-4 mb-4">
                                <h4 className="font-semibold mb-2">💰 Fee Calculation Preview</h4>
                                <div className="space-y-1 text-sm">
                                    <div className="flex justify-between">
                                        <span>Service Fee (1%):</span>
                                        <span className="font-medium">Rp {fees.serviceFee.toLocaleString('id-ID')}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>VAT (11%):</span>
                                        <span className="font-medium">Rp {fees.vat.toLocaleString('id-ID')}</span>
                                    </div>
                                    <div className="flex justify-between pt-2 border-t border-gray-300">
                                        <span className="font-semibold">Total Tagihan:</span>
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
                                className="px-6 py-2 bg-gradient-to-r from-orange-500 to-amber-500 text-white rounded-lg hover:from-orange-600 hover:to-amber-600 disabled:opacity-50">
                                {isSubmitting ? 'Updating...' : '💾 Update Data'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}
