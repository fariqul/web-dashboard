import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { router } from '@inertiajs/react';

// Helper function to format Rupiah
const formatRupiah = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
};

export default function PaymentDateDetailModal({ 
    isOpen, 
    onClose, 
    monthData, // { month: 'Y-m', fullName: 'December 2025', dates: '15, 22, 29' }
    filters = {} // { sheet: 'all', year: 'all', reason: 'all', bank: 'all' }
}) {
    const [loading, setLoading] = useState(false);
    const [data, setData] = useState(null);
    const [selectedDate, setSelectedDate] = useState('all'); // 'all' or specific date number
    const [expandedDates, setExpandedDates] = useState({});

    useEffect(() => {
        if (isOpen && monthData?.month) {
            fetchData();
        }
    }, [isOpen, monthData, selectedDate]);

    const fetchData = async () => {
        setLoading(true);
        try {
            const params = {
                month: monthData.month,
                sheet: filters.sheet || 'all',
                year: filters.year || 'all',
                reason: filters.reason || 'all',
                bank: filters.bank || 'all',
            };
            
            if (selectedDate !== 'all') {
                params.date = selectedDate;
            }
            
            const response = await axios.get('/api/sppd/payment-date-trips', { params });
            setData(response.data);
            
            // Expand all dates by default
            const expanded = {};
            response.data.dates.forEach(d => {
                expanded[d.date] = true;
            });
            setExpandedDates(expanded);
        } catch (error) {
            console.error('Error fetching payment date trips:', error);
        } finally {
            setLoading(false);
        }
    };

    const toggleDateExpansion = (date) => {
        setExpandedDates(prev => ({
            ...prev,
            [date]: !prev[date]
        }));
    };

    const copyTripNumber = (tripNumber) => {
        navigator.clipboard.writeText(tripNumber);
        // Optional: show a toast notification
    };

    const handleTripClick = (trip) => {
        // Navigate to destination detail with reason filter
        const params = new URLSearchParams();
        if (trip.reason) {
            params.set('reason', trip.reason);
        }
        if (filters.sheet && filters.sheet !== 'all') {
            params.set('sheet', filters.sheet);
        }
        if (filters.year && filters.year !== 'all') {
            params.set('year', filters.year);
        }
        router.visit(`/sppd/destination-detail?${params.toString()}`);
    };

    if (!isOpen) return null;

    // Parse available dates from monthData
    const availableDates = monthData?.dates?.split(', ').map(d => d.trim()) || [];

    return (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] flex flex-col shadow-2xl">
                {/* Header */}
                <div className="bg-gradient-to-r from-green-500 to-emerald-600 p-4 rounded-t-2xl">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="bg-white/20 p-2 rounded-lg">
                                <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h2 className="text-xl font-bold text-white">Detail Rencana Pembayaran</h2>
                                <p className="text-green-100 text-sm">{monthData?.fullName || 'Loading...'}</p>
                            </div>
                        </div>
                        <button
                            onClick={onClose}
                            className="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg transition"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {/* Summary & Filter */}
                <div className="p-4 border-b bg-gray-50">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        {/* Summary Stats */}
                        <div className="flex gap-4">
                            <div className="bg-white rounded-lg px-4 py-2 shadow-sm border">
                                <p className="text-xs text-gray-500">Total Trips</p>
                                <p className="text-xl font-bold text-green-600">{data?.total_trips || 0}</p>
                            </div>
                            <div className="bg-white rounded-lg px-4 py-2 shadow-sm border">
                                <p className="text-xs text-gray-500">Total Amount</p>
                                <p className="text-xl font-bold text-green-600">{formatRupiah(data?.total_amount || 0)}</p>
                            </div>
                        </div>

                        {/* Date Filter */}
                        <div className="flex items-center gap-2">
                            <span className="text-sm text-gray-600">Filter Tanggal:</span>
                            <select
                                value={selectedDate}
                                onChange={(e) => setSelectedDate(e.target.value)}
                                className="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            >
                                <option value="all">Semua Tanggal</option>
                                {availableDates.map(date => (
                                    <option key={date} value={date}>Tanggal {date}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                </div>

                {/* Content */}
                <div className="flex-1 overflow-y-auto p-4">
                    {loading ? (
                        <div className="flex items-center justify-center py-12">
                            <div className="animate-spin rounded-full h-10 w-10 border-4 border-green-500 border-t-transparent"></div>
                        </div>
                    ) : data?.dates?.length > 0 ? (
                        <div className="space-y-4">
                            {data.dates.map((dateGroup) => (
                                <div key={dateGroup.date} className="border rounded-xl overflow-hidden">
                                    {/* Date Header - Clickable */}
                                    <button
                                        onClick={() => toggleDateExpansion(dateGroup.date)}
                                        className="w-full flex items-center justify-between p-3 bg-gradient-to-r from-green-50 to-emerald-50 hover:from-green-100 hover:to-emerald-100 transition"
                                    >
                                        <div className="flex items-center gap-3">
                                            <div className="bg-green-500 text-white w-10 h-10 rounded-lg flex items-center justify-center font-bold">
                                                {dateGroup.date}
                                            </div>
                                            <div className="text-left">
                                                <p className="font-semibold text-gray-800">Tanggal {dateGroup.date}</p>
                                                <p className="text-sm text-gray-500">{dateGroup.count} trips • {formatRupiah(dateGroup.total_amount)}</p>
                                            </div>
                                        </div>
                                        <svg 
                                            className={`w-5 h-5 text-gray-500 transition-transform ${expandedDates[dateGroup.date] ? 'rotate-180' : ''}`} 
                                            fill="none" 
                                            stroke="currentColor" 
                                            viewBox="0 0 24 24"
                                        >
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    {/* Trips List */}
                                    {expandedDates[dateGroup.date] && (
                                        <div className="divide-y">
                                            {dateGroup.trips.map((trip, idx) => (
                                                <div 
                                                    key={trip.id || idx}
                                                    onClick={() => handleTripClick(trip)}
                                                    className="p-3 hover:bg-gray-50 cursor-pointer transition group"
                                                >
                                                    <div className="flex items-start justify-between gap-4">
                                                        <div className="flex-1 min-w-0">
                                                            <div className="flex items-center gap-2 mb-1">
                                                                <button 
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        copyTripNumber(trip.trip_number);
                                                                    }}
                                                                    className="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded font-mono hover:bg-gray-300 transition"
                                                                    title="Klik untuk copy"
                                                                >
                                                                    {trip.trip_number}
                                                                </button>
                                                                <span className="text-xs text-gray-400">•</span>
                                                                <span className="text-xs text-gray-500 truncate">{trip.bank}</span>
                                                            </div>
                                                            <p className="font-medium text-gray-800 truncate group-hover:text-green-600 transition">{trip.customer_name}</p>
                                                            <div className="flex items-center gap-2 mt-1 text-xs text-gray-500">
                                                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                </svg>
                                                                <span className="truncate">{trip.destination}</span>
                                                            </div>
                                                            <div className="flex items-center gap-2 mt-0.5 text-xs text-gray-500">
                                                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                                </svg>
                                                                <span className="truncate">{trip.reason}</span>
                                                            </div>
                                                        </div>
                                                        <div className="text-right flex-shrink-0">
                                                            <p className="font-bold text-green-600">{formatRupiah(trip.paid_amount)}</p>
                                                            <p className="text-xs text-gray-400 mt-1">
                                                                {new Date(trip.trip_begins_on).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })} 
                                                                {' - '}
                                                                {new Date(trip.trip_ends_on).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="flex flex-col items-center justify-center py-12 text-gray-500">
                            <svg className="w-16 h-16 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p className="font-semibold">Tidak ada data</p>
                            <p className="text-sm">Tidak ada trips di tanggal ini</p>
                        </div>
                    )}
                </div>

                {/* Footer */}
                <div className="p-4 border-t bg-gray-50 rounded-b-2xl">
                    <button
                        onClick={onClose}
                        className="w-full px-4 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition font-medium"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    );
}
