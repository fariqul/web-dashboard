import React, { useState } from 'react';
import { router } from '@inertiajs/react';

export default function DeleteAllServiceFeeModal({ isOpen, onClose, totalRecords = 0, hotelCount = 0, flightCount = 0 }) {
    const [serviceType, setServiceType] = useState('all');
    const [confirmation, setConfirmation] = useState('');
    const [isDeleting, setIsDeleting] = useState(false);

    if (!isOpen) return null;

    const getDeleteCount = () => {
        if (serviceType === 'all') return totalRecords;
        if (serviceType === 'hotel') return hotelCount;
        if (serviceType === 'flight') return flightCount;
        return 0;
    };

    const handleDelete = (e) => {
        e.preventDefault();
        
        if (confirmation !== 'HAPUS SEMUA') {
            alert('Ketik "HAPUS SEMUA" untuk konfirmasi penghapusan');
            return;
        }

        const count = getDeleteCount();
        const typeLabel = serviceType === 'all' ? 'SEMUA data Service Fee (Hotel + Flight)' : 
                         serviceType === 'hotel' ? 'SEMUA data Hotel' : 'SEMUA data Flight';
        
        const confirmMessage = `⚠️ PERINGATAN TERAKHIR!\n\nAnda akan menghapus ${count.toLocaleString()} data:\n${typeLabel}\n\nTindakan ini TIDAK DAPAT dibatalkan!\n\nApakah Anda yakin?`;
        
        if (!confirm(confirmMessage)) {
            return;
        }

        setIsDeleting(true);

        router.delete('/service-fee/delete-all', {
            data: {
                service_type: serviceType,
                confirmation: 'HAPUS SEMUA'
            },
            onSuccess: () => {
                setConfirmation('');
                setServiceType('all');
                onClose();
            },
            onError: (errors) => {
                console.error('Delete failed:', errors);
                alert('Gagal menghapus data. Silakan coba lagi.');
            },
            onFinish: () => {
                setIsDeleting(false);
            }
        });
    };

    const handleClose = () => {
        setConfirmation('');
        setServiceType('all');
        onClose();
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={handleClose}></div>

                <div className="relative bg-white rounded-lg shadow-xl max-w-md w-full">
                    {/* Warning Icon */}
                    <div className="flex items-center justify-center pt-6">
                        <div className="bg-red-100 rounded-full p-4">
                            <svg className="w-16 h-16 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>

                    {/* Content */}
                    <div className="p-6">
                        <h3 className="text-xl font-bold text-red-600 mb-2 text-center">
                            ⚠️ HAPUS SEMUA DATA ⚠️
                        </h3>
                        <p className="text-sm text-gray-600 mb-4 text-center">
                            Tindakan ini akan <span className="font-bold text-red-600">MENGHAPUS PERMANEN</span> semua data yang dipilih
                        </p>

                        {/* Data Summary */}
                        <div className="bg-gray-50 rounded-lg p-4 mb-4">
                            <h4 className="font-semibold text-gray-700 mb-2">Data Saat Ini:</h4>
                            <div className="grid grid-cols-3 gap-2 text-sm">
                                <div className="bg-blue-100 rounded p-2 text-center">
                                    <div className="font-bold text-blue-600">{hotelCount.toLocaleString()}</div>
                                    <div className="text-gray-600">🏨 Hotel</div>
                                </div>
                                <div className="bg-green-100 rounded p-2 text-center">
                                    <div className="font-bold text-green-600">{flightCount.toLocaleString()}</div>
                                    <div className="text-gray-600">✈️ Flight</div>
                                </div>
                                <div className="bg-purple-100 rounded p-2 text-center">
                                    <div className="font-bold text-purple-600">{totalRecords.toLocaleString()}</div>
                                    <div className="text-gray-600">📊 Total</div>
                                </div>
                            </div>
                        </div>

                        <form onSubmit={handleDelete} className="space-y-4">
                            {/* Service Type Selection */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Apa yang Ingin Dihapus? *
                                </label>
                                <div className="space-y-2">
                                    <label className="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-red-50 transition">
                                        <input
                                            type="radio"
                                            name="serviceType"
                                            value="all"
                                            checked={serviceType === 'all'}
                                            onChange={(e) => setServiceType(e.target.value)}
                                            className="w-4 h-4 text-red-600 focus:ring-red-500"
                                        />
                                        <span className="ml-3 text-gray-700">
                                            🗑️ <span className="font-semibold">SEMUA Data</span> (Hotel + Flight) 
                                            <span className="text-red-600 ml-1">({totalRecords.toLocaleString()} data)</span>
                                        </span>
                                    </label>
                                    <label className="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-blue-50 transition">
                                        <input
                                            type="radio"
                                            name="serviceType"
                                            value="hotel"
                                            checked={serviceType === 'hotel'}
                                            onChange={(e) => setServiceType(e.target.value)}
                                            className="w-4 h-4 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span className="ml-3 text-gray-700">
                                            🏨 <span className="font-semibold">Hotel Saja</span>
                                            <span className="text-blue-600 ml-1">({hotelCount.toLocaleString()} data)</span>
                                        </span>
                                    </label>
                                    <label className="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-green-50 transition">
                                        <input
                                            type="radio"
                                            name="serviceType"
                                            value="flight"
                                            checked={serviceType === 'flight'}
                                            onChange={(e) => setServiceType(e.target.value)}
                                            className="w-4 h-4 text-green-600 focus:ring-green-500"
                                        />
                                        <span className="ml-3 text-gray-700">
                                            ✈️ <span className="font-semibold">Flight Saja</span>
                                            <span className="text-green-600 ml-1">({flightCount.toLocaleString()} data)</span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            {/* Confirmation Input */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Ketik <span className="font-mono bg-red-100 px-2 py-1 rounded text-red-600">HAPUS SEMUA</span> untuk konfirmasi *
                                </label>
                                <input
                                    type="text"
                                    value={confirmation}
                                    onChange={(e) => setConfirmation(e.target.value.toUpperCase())}
                                    placeholder="Ketik HAPUS SEMUA"
                                    className={`w-full px-4 py-3 border-2 rounded-lg focus:ring-2 focus:ring-red-500 text-center font-mono text-lg ${
                                        confirmation === 'HAPUS SEMUA' 
                                            ? 'border-green-500 bg-green-50' 
                                            : 'border-gray-300'
                                    }`}
                                />
                            </div>

                            {/* Warning Box */}
                            <div className="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                                <div className="flex items-start">
                                    <svg className="w-5 h-5 text-red-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                    </svg>
                                    <div className="text-sm text-red-700">
                                        <p className="font-bold mb-1">Peringatan!</p>
                                        <ul className="list-disc list-inside space-y-1">
                                            <li>Tindakan ini <strong>TIDAK DAPAT DIBATALKAN</strong></li>
                                            <li>Semua data yang dipilih akan dihapus permanen</li>
                                            <li>Pastikan Anda sudah memiliki backup jika diperlukan</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            {/* Buttons */}
                            <div className="flex gap-3 pt-2">
                                <button
                                    type="button"
                                    onClick={handleClose}
                                    className="flex-1 px-4 py-3 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 font-semibold transition"
                                    disabled={isDeleting}
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={isDeleting || confirmation !== 'HAPUS SEMUA' || getDeleteCount() === 0}
                                    className={`flex-1 px-4 py-3 rounded-lg font-semibold transition flex items-center justify-center gap-2 ${
                                        confirmation === 'HAPUS SEMUA' && getDeleteCount() > 0
                                            ? 'bg-red-600 hover:bg-red-700 text-white'
                                            : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                    }`}
                                >
                                    {isDeleting ? (
                                        <>
                                            <svg className="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Menghapus...
                                        </>
                                    ) : (
                                        <>
                                            🗑️ Hapus {getDeleteCount().toLocaleString()} Data
                                        </>
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}
