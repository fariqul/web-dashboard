import React, { useState, useEffect } from 'react';
import MainLayout from '../Layouts/MainLayout';
import { router } from '@inertiajs/react';
import TransactionDetailModal from '../Components/TransactionDetailModal';
import toast, { Toaster } from 'react-hot-toast';

export default function RefundDetail({ 
    employeeName, 
    selectedSheet,
    selectedYear,
    transactions = { data: [], links: [], current_page: 1, last_page: 1, per_page: 10, total: 0 },
    filters = { search: '', sort: 'created_at', direction: 'desc' },
    summary = {}
}) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [selectedTransactionId, setSelectedTransactionId] = useState(null);
    const [modalMode, setModalMode] = useState('view');
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    
    // Auto search with debounce
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (searchTerm !== filters.search) {
                router.get('/cc-card/refund-detail', {
                    employee: employeeName,
                    sheet: selectedSheet,
                    year: selectedYear,
                    search: searchTerm,
                    sort: filters.sort,
                    direction: filters.direction,
                }, { 
                    preserveState: true,
                    preserveScroll: true,
                });
            }
        }, 500);
        
        return () => clearTimeout(timeoutId);
    }, [searchTerm]);
    
    const handleViewTransaction = (transactionId) => {
        setSelectedTransactionId(transactionId);
        setModalMode('view');
        setIsModalOpen(true);
    };
    
    const handleEditTransaction = (transactionId) => {
        setSelectedTransactionId(transactionId);
        setModalMode('edit');
        setIsModalOpen(true);
    };
    
    const handleDeleteTransaction = (transactionId) => {
        if (confirm(`Are you sure you want to delete this refund transaction?`)) {
            router.delete(`/cc-card/transaction/${transactionId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Refund deleted successfully!');
                },
                onError: () => {
                    toast.error('Failed to delete refund. Please try again.');
                }
            });
        }
    };
    
    const handleSort = (field) => {
        const newDirection = filters.sort === field && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get('/cc-card/refund-detail', {
            employee: employeeName,
            sheet: selectedSheet,
            year: selectedYear,
            search: searchTerm,
            sort: field,
            direction: newDirection,
        }, { 
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
            <svg className="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 15l7-7 7 7" />
            </svg>
        ) : (
            <svg className="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
            </svg>
        );
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

    const formatCurrency = (amount) => {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
    };

    return (
        <MainLayout>
            <div className="p-8">
                <Toaster position="top-right" />
                
                {/* Breadcrumb */}
                <div className="flex items-center gap-2 text-sm mb-6">
                    <button 
                        onClick={() => router.get('/cc-card', { sheet: selectedSheet })}
                        className="text-cyan-600 hover:text-cyan-800 font-medium"
                    >
                        CC Card Monitoring
                    </button>
                    <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                    </svg>
                    <span className="text-gray-600">Refund Detail</span>
                </div>

                {/* Header */}
                <div className="bg-gradient-to-r from-red-500 to-red-600 rounded-xl p-6 mb-6 text-white shadow-lg">
                    <div className="flex items-center gap-3 mb-2">
                        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        <h1 className="text-2xl font-bold">Refund Transactions</h1>
                    </div>
                    <p className="text-red-100 text-lg">{employeeName}</p>
                    {selectedSheet !== 'all' && (
                        <p className="text-red-200 text-sm mt-1">Sheet: {selectedSheet}</p>
                    )}
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div className="bg-white rounded-lg p-4 shadow border-l-4 border-red-500">
                        <p className="text-gray-600 text-sm">Total Refund Amount</p>
                        <p className="text-2xl font-bold text-red-600">{summary.totalAmount || 'Rp 0'}</p>
                    </div>
                    <div className="bg-white rounded-lg p-4 shadow border-l-4 border-orange-500">
                        <p className="text-gray-600 text-sm">Total Refunds</p>
                        <p className="text-2xl font-bold text-orange-600">{summary.totalRefunds || 0}</p>
                    </div>
                    <div className="bg-white rounded-lg p-4 shadow border-l-4 border-yellow-500">
                        <p className="text-gray-600 text-sm">Average per Refund</p>
                        <p className="text-2xl font-bold text-yellow-600">{summary.averageAmount || 'Rp 0'}</p>
                    </div>
                </div>

                {/* Transactions Table */}
                <div className="bg-white rounded-xl shadow-lg overflow-hidden">
                    {/* Search Bar */}
                    <div className="p-4 border-b border-gray-200">
                        <div className="relative">
                            <input
                                type="text"
                                placeholder="Search by booking ID or sheet..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            />
                            <svg className="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    {/* Table */}
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <button onClick={() => handleSort('booking_id')} className="flex items-center gap-1 hover:text-gray-700">
                                            Booking ID
                                            {getSortIcon('booking_id')}
                                        </button>
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Route
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <button onClick={() => handleSort('departure_date')} className="flex items-center gap-1 hover:text-gray-700">
                                            Trip Date
                                            {getSortIcon('departure_date')}
                                        </button>
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <button onClick={() => handleSort('sheet')} className="flex items-center gap-1 hover:text-gray-700">
                                            Sheet
                                            {getSortIcon('sheet')}
                                        </button>
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <button onClick={() => handleSort('payment_amount')} className="flex items-center gap-1 hover:text-gray-700">
                                            Amount
                                            {getSortIcon('payment_amount')}
                                        </button>
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {transactions.data && transactions.data.length > 0 ? (
                                    transactions.data.map((transaction, index) => (
                                        <tr key={transaction.id} className={index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="text-sm font-medium text-gray-900">{transaction.booking_id}</span>
                                            </td>
                                            <td className="px-6 py-4">
                                                {transaction.trip_destination_full ? (
                                                    <span className="text-sm text-gray-900">{transaction.trip_destination_full}</span>
                                                ) : (
                                                    <span className="text-sm text-gray-400 italic">-</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {transaction.departure_date ? (
                                                    <div className="text-sm">
                                                        <span className="text-gray-900">{formatDate(transaction.departure_date)}</span>
                                                        {transaction.return_date && transaction.return_date !== transaction.departure_date && (
                                                            <span className="text-gray-500"> - {formatDate(transaction.return_date)}</span>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <span className="text-sm text-gray-400 italic">-</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="text-sm text-gray-600">{transaction.sheet}</span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="text-sm font-semibold text-red-600">
                                                    {formatCurrency(transaction.payment_amount)}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right">
                                                <div className="flex justify-end gap-2">
                                                    <button
                                                        onClick={() => handleViewTransaction(transaction.id)}
                                                        className="text-cyan-600 hover:text-cyan-800"
                                                        title="View"
                                                    >
                                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        onClick={() => handleEditTransaction(transaction.id)}
                                                        className="text-yellow-600 hover:text-yellow-800"
                                                        title="Edit"
                                                    >
                                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        onClick={() => handleDeleteTransaction(transaction.id)}
                                                        className="text-red-600 hover:text-red-800"
                                                        title="Delete"
                                                    >
                                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="6" className="px-6 py-12 text-center text-gray-500">
                                            No refund transactions found
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {transactions.last_page > 1 && (
                        <div className="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div className="flex items-center justify-center">
                                <div className="flex gap-2 items-center">
                                    {/* Previous Button */}
                                    <button
                                        onClick={() => {
                                            if (transactions.current_page > 1) {
                                                router.get('/cc-card/refund-detail', {
                                                    employee: employeeName,
                                                    sheet: selectedSheet,
                                                    year: selectedYear,
                                                    search: searchTerm,
                                                    sort: filters.sort,
                                                    direction: filters.direction,
                                                    page: transactions.current_page - 1,
                                                });
                                            }
                                        }}
                                        disabled={transactions.current_page === 1}
                                        className={`px-3 py-1 rounded-md text-sm font-medium transition ${
                                            transactions.current_page === 1
                                                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                        }`}
                                    >
                                        ← Prev
                                    </button>

                                    {/* Page Info */}
                                    <span className="px-4 py-1 text-sm text-gray-700">
                                        Page {transactions.current_page} of {transactions.last_page}
                                    </span>

                                    {/* Next Button */}
                                    <button
                                        onClick={() => {
                                            if (transactions.current_page < transactions.last_page) {
                                                router.get('/cc-card/refund-detail', {
                                                    employee: employeeName,
                                                    sheet: selectedSheet,
                                                    year: selectedYear,
                                                    search: searchTerm,
                                                    sort: filters.sort,
                                                    direction: filters.direction,
                                                    page: transactions.current_page + 1,
                                                });
                                            }
                                        }}
                                        disabled={transactions.current_page === transactions.last_page}
                                        className={`px-3 py-1 rounded-md text-sm font-medium transition ${
                                            transactions.current_page === transactions.last_page
                                                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                        }`}
                                    >
                                        Next →
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Back Button */}
                <div className="mt-6">
                    <button
                        onClick={() => router.get('/cc-card', { sheet: selectedSheet })}
                        className="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition font-medium flex items-center gap-2"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to CC Card
                    </button>
                </div>

                {/* Transaction Detail Modal */}
                {isModalOpen && (
                    <TransactionDetailModal
                        isOpen={isModalOpen}
                        onClose={() => setIsModalOpen(false)}
                        transactionId={selectedTransactionId}
                        mode={modalMode}
                    />
                )}
            </div>
        </MainLayout>
    );
}
