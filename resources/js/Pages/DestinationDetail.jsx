import React, { useState, useEffect } from 'react';
import MainLayout from '../Layouts/MainLayout';
import { router } from '@inertiajs/react';
import TransactionDetailModal from '../Components/TransactionDetailModal';
import toast, { Toaster } from 'react-hot-toast';

export default function DestinationDetail({ 
    destination, 
    selectedSheet,
    selectedYear,
    transactions = { data: [], links: [], current_page: 1, last_page: 1, per_page: 10, total: 0 },
    filters = { search: '', sort: 'departure_date', direction: 'desc' },
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
                router.get('/cc-card/destination-detail', {
                    destination,
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
        }, 500); // Delay 500ms setelah user berhenti mengetik
        
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
    
    const handleDeleteTransaction = (transactionId, employeeName) => {
        if (confirm(`Are you sure you want to delete this transaction for ${employeeName}?`)) {
            router.delete(`/cc-card/transaction/${transactionId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Transaction deleted successfully!');
                },
                onError: () => {
                    toast.error('Failed to delete transaction. Please try again.');
                }
            });
        }
    };
    
    const handleSort = (field) => {
        const newDirection = filters.sort === field && filters.direction === 'asc' ? 'desc' : 'asc';
        router.get('/cc-card/destination-detail', {
            destination,
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
            <svg className="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 15l7-7 7 7" />
            </svg>
        ) : (
            <svg className="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
            </svg>
        );
    };
    
    const formatDate = (dateString) => {
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
                {/* Breadcrumb */}
                <div className="flex items-center gap-2 text-sm mb-6">
                    <button 
                        onClick={() => {
                            const params = {};
                            if (selectedYear && selectedYear !== 'all') {
                                params.sheet = `year:${selectedYear}`;
                            } else if (selectedSheet && selectedSheet !== 'all') {
                                params.sheet = selectedSheet;
                            }
                            router.get('/cc-card', params);
                        }}
                        className="text-cyan-600 hover:text-cyan-700 font-medium"
                    >
                        CC Card Monitoring
                    </button>
                    <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                    </svg>
                    <span className="text-gray-600">Destination Detail</span>
                </div>

                {/* Header */}
                <div className="mb-6">
                    <h1 className="text-3xl font-bold mb-2">{destination}</h1>
                    <p className="text-gray-600">
                        {selectedYear && selectedYear !== 'all' 
                            ? `year:${selectedYear}` 
                            : (selectedSheet === 'all' ? 'All Sheets' : selectedSheet)
                        }
                    </p>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-4 gap-6 mb-6">
                    <div className="bg-gradient-to-br from-green-400 to-green-500 rounded-lg p-6 text-white shadow-lg">
                        <p className="text-green-100 text-sm mb-2">Total Amount</p>
                        <p className="text-3xl font-bold">{summary.totalAmount}</p>
                    </div>
                    <div className="bg-gradient-to-br from-blue-400 to-blue-500 rounded-lg p-6 text-white shadow-lg">
                        <p className="text-blue-100 text-sm mb-2">Total Trips</p>
                        <p className="text-3xl font-bold">{summary.totalTrips}</p>
                    </div>
                    <div className="bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-lg p-6 text-white shadow-lg">
                        <p className="text-yellow-100 text-sm mb-2">Average Amount</p>
                        <p className="text-3xl font-bold">{summary.averageAmount}</p>
                    </div>
                    <div className="bg-gradient-to-br from-purple-400 to-purple-500 rounded-lg p-6 text-white shadow-lg">
                        <p className="text-purple-100 text-sm mb-2">Unique Employees</p>
                        <p className="text-3xl font-bold">{summary.uniqueEmployees}</p>
                    </div>
                </div>

                {/* Transactions Table */}
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <div className="flex justify-between items-center mb-4">
                            <div>
                                <h2 className="text-xl font-bold">Transaction Details</h2>
                                <p className="text-sm text-gray-600 mt-1">
                                    {transactions.total} transaction{transactions.total !== 1 ? 's' : ''} found
                                </p>
                            </div>
                            
                            {/* Search Bar */}
                            <div className="flex gap-2 items-center">
                                <div className="relative">
                                    <input
                                        type="text"
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        placeholder="🔍 Search by name, booking ID, personnel no..."
                                        className="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 w-96 pr-10"
                                    />
                                    {searchTerm && (
                                        <button
                                            type="button"
                                            onClick={() => setSearchTerm('')}
                                            className="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                        >
                                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-100 border-b border-gray-200">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        No
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        <button 
                                            onClick={() => handleSort('employee_name')}
                                            className="flex items-center gap-1 hover:text-cyan-600 transition"
                                        >
                                            Employee Name {getSortIcon('employee_name')}
                                        </button>
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        <button 
                                            onClick={() => handleSort('booking_id')}
                                            className="flex items-center gap-1 hover:text-cyan-600 transition"
                                        >
                                            Booking ID {getSortIcon('booking_id')}
                                        </button>
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        <button 
                                            onClick={() => handleSort('personel_number')}
                                            className="flex items-center gap-1 hover:text-cyan-600 transition"
                                        >
                                            Personnel No. {getSortIcon('personel_number')}
                                        </button>
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        <button 
                                            onClick={() => handleSort('trip_number')}
                                            className="flex items-center gap-1 hover:text-cyan-600 transition"
                                        >
                                            Trip Number {getSortIcon('trip_number')}
                                        </button>
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        <button 
                                            onClick={() => handleSort('departure_date')}
                                            className="flex items-center gap-1 hover:text-cyan-600 transition"
                                        >
                                            Departure Date {getSortIcon('departure_date')}
                                        </button>
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        <button 
                                            onClick={() => handleSort('return_date')}
                                            className="flex items-center gap-1 hover:text-cyan-600 transition"
                                        >
                                            Return Date {getSortIcon('return_date')}
                                        </button>
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        <button 
                                            onClick={() => handleSort('duration_days')}
                                            className="flex items-center gap-1 hover:text-cyan-600 transition"
                                        >
                                            Duration {getSortIcon('duration_days')}
                                        </button>
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        <button 
                                            onClick={() => handleSort('payment_amount')}
                                            className="flex items-center gap-1 hover:text-cyan-600 transition ml-auto"
                                        >
                                            Amount {getSortIcon('payment_amount')}
                                        </button>
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Sheet
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {transactions.data && transactions.data.length > 0 ? (
                                    transactions.data.map((transaction, index) => (
                                        <tr key={transaction.id} className="hover:bg-gray-50 transition">
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {(transactions.current_page - 1) * transactions.per_page + index + 1}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm font-medium text-gray-900">
                                                    {transaction.employee_name}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {transaction.booking_id}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {transaction.personel_number}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {transaction.trip_number}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {formatDate(transaction.departure_date)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {formatDate(transaction.return_date)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {transaction.duration_days} days
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600 text-right">
                                                {formatCurrency(transaction.payment_amount)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="px-2 py-1 text-xs font-medium bg-cyan-100 text-cyan-800 rounded">
                                                    {transaction.sheet}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-center">
                                                <div className="flex items-center justify-center gap-2">
                                                    <button
                                                        onClick={() => handleViewTransaction(transaction.id)}
                                                        className="px-3 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 transition font-medium"
                                                        title="View Details"
                                                    >
                                                        👁️ View
                                                    </button>
                                                    <button
                                                        onClick={() => handleEditTransaction(transaction.id)}
                                                        className="px-3 py-1 text-xs bg-green-500 text-white rounded hover:bg-green-600 transition font-medium"
                                                        title="Edit Transaction"
                                                    >
                                                        ✏️ Edit
                                                    </button>
                                                    <button
                                                        onClick={() => handleDeleteTransaction(transaction.id, transaction.employee_name)}
                                                        className="px-3 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 transition font-medium"
                                                        title="Delete Transaction"
                                                    >
                                                        🗑️ Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="11" className="px-6 py-12 text-center text-gray-500">
                                            <svg className="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p className="font-semibold">No transactions found</p>
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
                                                router.get('/cc-card/destination-detail', {
                                                    destination,
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

                                    {/* Page Numbers */}
                                    {(() => {
                                        const current = transactions.current_page;
                                        const last = transactions.last_page;
                                        const pages = [];

                                        // Always show first page
                                        pages.push(1);
                                        
                                        // Show pages around current page
                                        for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
                                            if (!pages.includes(i)) {
                                                pages.push(i);
                                            }
                                        }
                                        
                                        // Always show last page
                                        if (!pages.includes(last)) {
                                            pages.push(last);
                                        }

                                        return pages.map((page, index) => {
                                            // Show ellipsis if there's a gap
                                            if (index > 0 && page - pages[index - 1] > 1) {
                                                return (
                                                    <React.Fragment key={`ellipsis-${page}`}>
                                                        <span className="px-2 text-gray-500">...</span>
                                                        <button
                                                            onClick={() => {
                                                                router.get('/cc-card/destination-detail', {
                                                                    destination,
                                                                    sheet: selectedSheet,
                                                                    year: selectedYear,
                                                                    search: searchTerm,
                                                                    sort: filters.sort,
                                                                    direction: filters.direction,
                                                                    page: page,
                                                                });
                                                            }}
                                                            className={`px-3 py-1 rounded-md text-sm font-medium transition ${
                                                                current === page
                                                                    ? 'bg-cyan-500 text-white'
                                                                    : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                                            }`}
                                                        >
                                                            {page}
                                                        </button>
                                                    </React.Fragment>
                                                );
                                            }

                                            return (
                                                <button
                                                    key={page}
                                                    onClick={() => {
                                                        router.get('/cc-card/destination-detail', {
                                                            destination,
                                                            sheet: selectedSheet,
                                                            year: selectedYear,
                                                            search: searchTerm,
                                                            sort: filters.sort,
                                                            direction: filters.direction,
                                                            page: page,
                                                        });
                                                    }}
                                                    className={`px-3 py-1 rounded-md text-sm font-medium transition ${
                                                        current === page
                                                            ? 'bg-cyan-500 text-white'
                                                            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                                    }`}
                                                >
                                                    {page}
                                                </button>
                                            );
                                        });
                                    })()}

                                    {/* Next Button */}
                                    <button
                                        onClick={() => {
                                            if (transactions.current_page < transactions.last_page) {
                                                router.get('/cc-card/destination-detail', {
                                                    destination,
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
                        className="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition font-semibold flex items-center gap-2"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Dashboard
                    </button>
                </div>
            </div>
            
            {/* Transaction Detail Modal */}
            <TransactionDetailModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                transactionId={selectedTransactionId}
                mode={modalMode}
            />
            
            {/* Toast Notification */}
            <Toaster position="top-right" />
        </MainLayout>
    );
}
