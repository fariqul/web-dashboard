import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import toast from 'react-hot-toast';

const AddSppdModal = ({ isOpen, onClose }) => {
    const currentYear = new Date().getFullYear();
    const currentMonth = new Date().getMonth() + 1;

    const [formData, setFormData] = useState({
        trip_number: '',
        customer_name: '',
        trip_destination: '',
        reason_for_trip: '',
        trip_begins_on: '',
        trip_ends_on: '',
        planned_payment_date: '',
        paid_amount: '',
        beneficiary_bank_name: '',
        sheet_month: currentMonth,
        sheet_year: currentYear
    });

    const [errors, setErrors] = useState({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
        // Clear error for this field
        if (errors[name]) {
            setErrors(prev => ({ ...prev, [name]: null }));
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setIsSubmitting(true);
        setErrors({});

        try {
            router.post('/sppd/store', formData, {
                onSuccess: () => {
                    toast.success('Data SPPD berhasil ditambahkan!');
                    onClose();
                    // Reset form
                    setFormData({
                        trip_number: '',
                        customer_name: '',
                        trip_destination: '',
                        reason_for_trip: '',
                        trip_begins_on: '',
                        trip_ends_on: '',
                        planned_payment_date: '',
                        paid_amount: '',
                        beneficiary_bank_name: '',
                        sheet_month: currentMonth,
                        sheet_year: currentYear
                    });
                },
                onError: (errors) => {
                    setErrors(errors);
                    toast.error('Gagal menambahkan data. Periksa kembali input Anda.');
                },
                onFinish: () => {
                    setIsSubmitting(false);
                }
            });
        } catch (error) {
            console.error('Error submitting form:', error);
            toast.error('Terjadi kesalahan saat menambahkan data.');
            setIsSubmitting(false);
        }
    };

    const handleClose = () => {
        if (!isSubmitting) {
            onClose();
            setErrors({});
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                {/* Header */}
                <div className="sticky top-0 bg-gradient-to-r from-blue-600 to-cyan-600 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
                    <h2 className="text-xl font-bold">Tambah Data SPPD Manual</h2>
                    <button
                        onClick={handleClose}
                        disabled={isSubmitting}
                        className="text-white hover:text-gray-200 text-2xl font-bold disabled:opacity-50"
                    >
                        ×
                    </button>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="p-6 space-y-4">
                    {/* Period Selection */}
                    <div className="grid grid-cols-2 gap-4 pb-4 border-b">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Bulan <span className="text-red-500">*</span>
                            </label>
                            <select
                                name="sheet_month"
                                value={formData.sheet_month}
                                onChange={handleChange}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required
                            >
                                {[1,2,3,4,5,6,7,8,9,10,11,12].map(month => (
                                    <option key={month} value={month}>
                                        {new Date(2000, month - 1).toLocaleString('id-ID', { month: 'long' })}
                                    </option>
                                ))}
                            </select>
                            {errors.sheet_month && <p className="text-red-500 text-xs mt-1">{errors.sheet_month}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Tahun <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="number"
                                name="sheet_year"
                                value={formData.sheet_year}
                                onChange={handleChange}
                                min="2020"
                                max="2100"
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required
                            />
                            {errors.sheet_year && <p className="text-red-500 text-xs mt-1">{errors.sheet_year}</p>}
                        </div>
                    </div>

                    {/* Trip Number */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Trip Number <span className="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="trip_number"
                            value={formData.trip_number}
                            onChange={handleChange}
                            placeholder="e.g., 4120177504"
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                        />
                        {errors.trip_number && <p className="text-red-500 text-xs mt-1">{errors.trip_number}</p>}
                    </div>

                    {/* Customer Name */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Nama Pegawai <span className="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="customer_name"
                            value={formData.customer_name}
                            onChange={handleChange}
                            placeholder="e.g., ALFIAN ROMADHAN"
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                        />
                        {errors.customer_name && <p className="text-red-500 text-xs mt-1">{errors.customer_name}</p>}
                    </div>

                    {/* Trip Destination */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Tujuan Perjalanan <span className="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="trip_destination"
                            value={formData.trip_destination}
                            onChange={handleChange}
                            placeholder="e.g., Kab. Bulukumba - Kota Makassar"
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                        />
                        {errors.trip_destination && <p className="text-red-500 text-xs mt-1">{errors.trip_destination}</p>}
                    </div>

                    {/* Reason for Trip */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Keperluan Perjalanan
                        </label>
                        <input
                            type="text"
                            name="reason_for_trip"
                            value={formData.reason_for_trip}
                            onChange={handleChange}
                            placeholder="e.g., MENGHADIRI RAPAT DI UP2D SULSELRABAR"
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        {errors.reason_for_trip && <p className="text-red-500 text-xs mt-1">{errors.reason_for_trip}</p>}
                    </div>

                    {/* Trip Dates */}
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Tanggal Mulai <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="date"
                                name="trip_begins_on"
                                value={formData.trip_begins_on}
                                onChange={handleChange}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required
                            />
                            {errors.trip_begins_on && <p className="text-red-500 text-xs mt-1">{errors.trip_begins_on}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Tanggal Selesai <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="date"
                                name="trip_ends_on"
                                value={formData.trip_ends_on}
                                onChange={handleChange}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required
                            />
                            {errors.trip_ends_on && <p className="text-red-500 text-xs mt-1">{errors.trip_ends_on}</p>}
                        </div>
                    </div>

                    {/* Planned Payment Date */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Rencana Tanggal Bayar
                        </label>
                        <input
                            type="date"
                            name="planned_payment_date"
                            value={formData.planned_payment_date}
                            onChange={handleChange}
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        {errors.planned_payment_date && <p className="text-red-500 text-xs mt-1">{errors.planned_payment_date}</p>}
                        <p className="text-xs text-gray-500 mt-1">Opsional: Tanggal rencana pembayaran SPPD</p>
                    </div>

                    {/* Paid Amount */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Jumlah Pembayaran <span className="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            name="paid_amount"
                            value={formData.paid_amount}
                            onChange={handleChange}
                            placeholder="e.g., 650000"
                            min="0"
                            step="1000"
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                        />
                        {errors.paid_amount && <p className="text-red-500 text-xs mt-1">{errors.paid_amount}</p>}
                    </div>

                    {/* Bank Name */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Nama Bank
                        </label>
                        <input
                            type="text"
                            name="beneficiary_bank_name"
                            value={formData.beneficiary_bank_name}
                            onChange={handleChange}
                            placeholder="e.g., PT BANK SYARIAH INDONESIA"
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        {errors.beneficiary_bank_name && <p className="text-red-500 text-xs mt-1">{errors.beneficiary_bank_name}</p>}
                    </div>

                    {/* Action Buttons */}
                    <div className="flex justify-end gap-3 pt-4 border-t">
                        <button
                            type="button"
                            onClick={handleClose}
                            disabled={isSubmitting}
                            className="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 disabled:opacity-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={isSubmitting}
                            className="px-4 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-md hover:from-blue-700 hover:to-cyan-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {isSubmitting ? 'Menyimpan...' : 'Simpan'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default AddSppdModal;
