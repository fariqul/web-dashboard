import React, { useState, useEffect } from 'react';
import MainLayout from '../Layouts/MainLayout';
import { router } from '@inertiajs/react';
import toast, { Toaster } from 'react-hot-toast';
import axios from 'axios';

export default function TripDestinationDetail({ 
    destination, 
    selectedSheet,
    selectedYear,
    selectedReason = 'all',
    selectedBank = 'all',
    selectedStatus = 'all',
    transactions = { data: [], links: [], current_page: 1, last_page: 1, per_page: 10, total: 0 },
    filters = { search: '', sort: 'trip_begins_on', direction: 'desc' },
    summary = {}
}) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    
    // Auto search with debounce
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (searchTerm !== filters.search) {
                const params = {
                    destination,
                    sheet: selectedSheet,
                    year: selectedYear,
                    search: searchTerm,
                    sort: filters.sort,
                    direction: filters.direction,
                };
                if (selectedReason !== 'all') params.reason = selectedReason;
                if (selectedBank !== 'all') params.bank = selectedBank;
                
                router.get('/sppd/destination-detail', params, { 
                    preserveState: true,
                    preserveScroll: true,
                });
            }
        }, 500);
        
        return () => clearTimeout(timeoutId);
    }, [searchTerm]);
    
    const handleSort = (field) => {
        const newDirection = filters.sort === field && filters.direction === 'asc' ? 'desc' : 'asc';
        const params = {
            destination,
            sheet: selectedSheet,
            year: selectedYear,
            search: searchTerm,
            sort: field,
            direction: newDirection,
        };
        if (selectedReason !== 'all') params.reason = selectedReason;
        if (selectedBank !== 'all') params.bank = selectedBank;
        
        router.get('/sppd/destination-detail', params, { 
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getSortIcon = (field) => {
        if (filters.sort !== field) {
            return (
                <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
            );
        }
        return filters.direction === 'asc' ? (
            <svg className="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 15l7-7 7 7" />
            </svg>
        ) : (
            <svg className="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
            </svg>
        );
    };
    
    const handlePageChange = (url) => {
        if (!url) return;
        router.get(url, {}, { preserveState: true, preserveScroll: true });
    };
    
    const formatRupiah = (amount) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    };
    
    const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', { 
            day: '2-digit', 
            month: 'short', 
            year: 'numeric' 
        });
    };
    
    const handleDeleteTrip = async (tripId, tripNumber) => {
        if (!confirm(`Are you sure you want to delete trip ${tripNumber}? This action cannot be undone.`)) {
            return;
        }
        
        try {
            await axios.delete(`/sppd/transaction/${tripId}`);
            toast.success('Trip deleted successfully!');
            router.reload();
        } catch (error) {
            toast.error('Failed to delete trip');
        }
    };

    return (
        <MainLayout>
            <Toaster position="top-right" />
            
            {/* Header */}
            <div className="bg-gradient-to-r from-purple-600 via-purple-500 to-pink-500 text-white p-8 shadow-lg">
                <div className="max-w-7xl mx-auto">
                    <div className="flex items-center gap-4 mb-4">
                        <button
                            onClick={() => {
                                const params = new URLSearchParams();
                                if (selectedSheet && selectedSheet !== 'all') params.append('sheet', selectedSheet);
                                if (selectedReason && selectedReason !== 'all') params.append('reason', selectedReason);
                                if (selectedBank && selectedBank !== 'all') params.append('bank', selectedBank);
                                if (selectedStatus && selectedStatus !== 'all') params.append('status', selectedStatus);
                                router.visit(`/sppd?${params.toString()}`);
                            }}
                            className="p-2 hover:bg-white/20 rounded-lg transition"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div className="flex-1">
                            <h1 className="text-3xl font-bold">{destination}</h1>
                            <p className="text-purple-100 mt-1">Trip Destination Details</p>
                        </div>
                    </div>
                    
                    {/* Summary Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                        <div className="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                            <div className="text-purple-100 text-sm mb-1">Total Trips</div>
                            <div className="text-2xl font-bold">{summary.totalTrips || 0}</div>
                        </div>
                        <div className="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                            <div className="text-purple-100 text-sm mb-1">Total Amount</div>
                            <div className="text-2xl font-bold">{formatRupiah(summary.totalAmount || 0)}</div>
                        </div>
                        <div className="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                            <div className="text-purple-100 text-sm mb-1">Unique Travelers</div>
                            <div className="text-2xl font-bold">{summary.uniqueCustomers || 0}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="p-8 bg-gray-50 min-h-screen">
                <div className="max-w-7xl mx-auto">
                    {/* Search Bar */}
                    <div className="bg-white rounded-xl shadow-lg p-6 mb-6">
                        <div className="flex items-center gap-4">
                            <div className="flex-1">
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        placeholder="Search by trip number, customer name, reason..."
                                        className="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:ring-4 focus:ring-purple-100 transition"
                                    />
                                </div>
                            </div>
                            <div className="text-sm text-gray-600">
                                Showing {transactions.data.length} of {transactions.total} trips
                            </div>
                        </div>
                    </div>

                    {/* Transactions Table */}
                    <div className="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead className="bg-gradient-to-r from-purple-500 to-pink-500 text-white">
                                    <tr>
                                        <th className="px-6 py-4 text-left text-sm font-semibold">
                                            <button 
                                                onClick={() => handleSort('trip_number')}
                                                className="flex items-center gap-2 hover:text-purple-100 transition"
                                            >
                                                Trip Number {getSortIcon('trip_number')}
                                            </button>
                                        </th>
                                        <th className="px-6 py-4 text-left text-sm font-semibold">
                                            <button 
                                                onClick={() => handleSort('customer_name')}
                                                className="flex items-center gap-2 hover:text-purple-100 transition"
                                            >
                                                Traveler Name {getSortIcon('customer_name')}
                                            </button>
                                        </th>
                                        <th className="px-6 py-4 text-left text-sm font-semibold">
                                            Route
                                        </th>
                                        <th className="px-6 py-4 text-left text-sm font-semibold">
                                            Reason
                                        </th>
                                        <th className="px-6 py-4 text-left text-sm font-semibold">
                                            <button 
                                                onClick={() => handleSort('trip_begins_on')}
                                                className="flex items-center gap-2 hover:text-purple-100 transition"
                                            >
                                                Departure {getSortIcon('trip_begins_on')}
                                            </button>
                                        </th>
                                        <th className="px-6 py-4 text-left text-sm font-semibold">
                                            <button 
                                                onClick={() => handleSort('trip_ends_on')}
                                                className="flex items-center gap-2 hover:text-purple-100 transition"
                                            >
                                                Return {getSortIcon('trip_ends_on')}
                                            </button>
                                        </th>
                                        <th className="px-6 py-4 text-left text-sm font-semibold">
                                            Duration
                                        </th>
                                        <th className="px-6 py-4 text-left text-sm font-semibold">
                                            <button 
                                                onClick={() => handleSort('paid_amount')}
                                                className="flex items-center gap-2 hover:text-purple-100 transition"
                                            >
                                                Amount {getSortIcon('paid_amount')}
                                            </button>
                                        </th>
                                        <th className="px-6 py-4 text-left text-sm font-semibold">
                                            Bank
                                        </th>
                                        <th className="px-6 py-4 text-left text-sm font-semibold">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {transactions.data && transactions.data.length > 0 ? (
                                        transactions.data.map((trip, index) => (
                                            <tr key={trip.id} className="hover:bg-purple-50 transition">
                                                <td className="px-6 py-4">
                                                    <span className="font-mono text-sm font-semibold text-purple-600">
                                                        {trip.trip_number}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="font-medium text-gray-900">{trip.customer_name}</div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="text-sm text-gray-700">
                                                        {trip.trip_destination_full || trip.destination || '-'}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="text-sm text-gray-600 max-w-xs truncate" title={trip.reason_for_trip}>
                                                        {trip.reason_for_trip || '-'}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="text-sm text-gray-600">
                                                        {formatDate(trip.trip_begins_on)}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="text-sm text-gray-600">
                                                        {formatDate(trip.trip_ends_on)}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                        {trip.duration_days ? `${trip.duration_days} day${trip.duration_days > 1 ? 's' : ''}` : '-'}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="font-semibold text-green-600">
                                                        {formatRupiah(trip.paid_amount)}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="text-sm text-gray-600">
                                                        {trip.beneficiary_bank_name || '-'}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <button
                                                        onClick={() => handleDeleteTrip(trip.id, trip.trip_number)}
                                                        className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                                                        title="Delete trip"
                                                    >
                                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="10" className="px-6 py-12 text-center">
                                                <div className="text-gray-500">
                                                    <svg className="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    <p className="text-lg font-semibold mb-1">No trips found</p>
                                                    <p className="text-sm">Try adjusting your search or filters</p>
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {transactions.last_page > 1 && (
                            <div className="bg-gray-50 px-6 py-4 border-t border-gray-200">
                                <div className="flex items-center justify-between">
                                    <div className="text-sm text-gray-600">
                                        Page {transactions.current_page} of {transactions.last_page}
                                    </div>
                                    <div className="flex gap-2">
                                        {transactions.links.map((link, index) => {
                                            if (link.url === null) {
                                                return (
                                                    <button
                                                        key={index}
                                                        disabled
                                                        className="px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed"
                                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                                    />
                                                );
                                            }
                                            
                                            return (
                                                <button
                                                    key={index}
                                                    onClick={() => handlePageChange(link.url)}
                                                    className={`px-4 py-2 text-sm font-medium rounded-lg transition ${
                                                        link.active
                                                            ? 'bg-purple-600 text-white'
                                                            : 'bg-white text-gray-700 hover:bg-purple-50 border border-gray-300'
                                                    }`}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            );
                                        })}
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}
