import React, { useState, useEffect } from 'react';
import MainLayout from '../Layouts/MainLayout';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, ResponsiveContainer, Tooltip, Legend, PieChart, Pie, Cell } from 'recharts';
import { router } from '@inertiajs/react';
import NewTransactionModal from '../Components/NewTransactionModal';
import DeleteAllCCCardModal from '../Components/DeleteAllCCCardModal';
import toast, { Toaster } from 'react-hot-toast';
import axios from 'axios';

// Helper function to format summary display (auto unit selection)
const formatSummaryDisplay = (value) => {
    if (value >= 1000000000) {
        return {
            value: (value / 1000000000).toFixed(1),
            unit: 'M' // Miliar
        };
    } else if (value >= 1000000) {
        return {
            value: (value / 1000000).toFixed(1),
            unit: 'Jt' // Juta
        };
    } else if (value >= 1000) {
        return {
            value: (value / 1000).toFixed(1),
            unit: 'Rb' // Ribu
        };
    }
    return {
        value: value.toFixed(0),
        unit: ''
    };
};

// Helper function to format Rupiah
const formatRupiah = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
};

// Custom Tooltip Component
const CustomTooltip = ({ active, payload, label }) => {
    if (active && payload && payload.length) {
        // Get full sheet name from payload if available
        const fullName = payload[0]?.payload?.fullName || label;
        
        return (
            <div style={{
                backgroundColor: '#fff',
                border: '1px solid #e5e7eb',
                borderRadius: '8px',
                padding: '8px',
                fontSize: '12px'
            }}>
                <p style={{ fontWeight: 'bold', marginBottom: '4px' }}>{fullName}</p>
                {payload.map((entry, index) => (
                    <p key={index} style={{ color: entry.color, margin: '2px 0' }}>
                        {entry.name}: Rp {entry.value}M
                    </p>
                ))}
            </div>
        );
    }
    return null;
};

export default function CCCardMonitoring({ 
    totalPayment = 0,
    totalAdminInterest = 0,
    totalRefund = 0,
    sheetComparison = [],
    monthlyChartData = null,
    destinations = [],
    refundList = [],
    availableFilters = [],
    selectedFilter = 'all',
    selectedCard = 'all',
    topEmployeesByCount = [],
    topEmployeesByAmount = [],
    paymentRefundRatio = { payment: 0, refund: 0, paymentPercentage: 0, refundPercentage: 0 },
    flash = {}
}) {
    const [isFilterDropdownOpen, setIsFilterDropdownOpen] = useState(false);
    const [isYearDropdownOpen, setIsYearDropdownOpen] = useState(false);
    const [isCardDropdownOpen, setIsCardDropdownOpen] = useState(false);
    const [chartMode, setChartMode] = useState('monthly'); // 'monthly' or 'comparison'
    const [visibleDestinationsCount, setVisibleDestinationsCount] = useState(5);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isDeleteAllModalOpen, setIsDeleteAllModalOpen] = useState(false);
    const [transactionTypeFilter, setTransactionTypeFilter] = useState('payment'); // 'payment', 'refund', 'all'
    const [searchQuery, setSearchQuery] = useState('');
    const [searchTimeout, setSearchTimeout] = useState(null);
    
    // Show flash messages with react-hot-toast
    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash]);
    
    // Extract available years from availableFilters
    const availableYears = availableFilters
        .filter(f => f.type === 'year')
        .map(f => f.value.replace('year:', ''))
        .filter(year => year && year.trim() !== ''); // Remove empty years
    
    // Initialize collapsedYears state - all years collapsed by default
    const [collapsedYears, setCollapsedYears] = useState(() => {
        const initialCollapsed = {};
        availableFilters
            .filter(f => f.type === 'header')
            .forEach(f => {
                const year = f.value.replace('header:', '');
                initialCollapsed[year] = true; // true = collapsed/hidden
            });
        return initialCollapsed;
    });
    
    // Handle search with debounce
    const handleSearchChange = (e) => {
        const value = e.target.value;
        setSearchQuery(value);
        
        // Clear existing timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Set new timeout for search (500ms debounce)
        const timeout = setTimeout(() => {
            router.get('/cc-card', { 
                sheet: selectedFilter,
                search: value 
            }, { 
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setVisibleDestinationsCount(5);
                }
            });
        }, 500);
        
        setSearchTimeout(timeout);
    };
    
    // Clear search
    const handleClearSearch = () => {
        setSearchQuery('');
        router.get('/cc-card', { 
            sheet: selectedFilter
        }, { 
            preserveState: true,
            preserveScroll: true
        });
    };
    
    // Calculate total transactions for delete modal
    const totalTransactions = (destinations?.length || 0) + (refundList?.length || 0);
    
    // Get filtered destinations based on transaction type (search is handled by backend)
    const getFilteredDestinations = () => {
        let list = [];
        
        if (transactionTypeFilter === 'payment') {
            list = Array.isArray(destinations) ? destinations : [];
        } else if (transactionTypeFilter === 'refund') {
            list = Array.isArray(refundList) ? refundList : [];
        } else {
            // Combine both
            const payments = Array.isArray(destinations) ? destinations : [];
            const refunds = Array.isArray(refundList) ? refundList : [];
            list = [...payments, ...refunds].sort((a, b) => (b.rawAmount || 0) - (a.rawAmount || 0));
        }
        
        return list;
    };
    
    const filteredDestinations = getFilteredDestinations();
    
    const handleFilterChange = (filterValue) => {
        router.get('/cc-card', { sheet: filterValue, card: selectedCard, search: searchQuery || undefined }, { 
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                setVisibleDestinationsCount(5);
            }
        });
        setIsFilterDropdownOpen(false);
    };
    
    const handleCardFilterChange = (cardValue) => {
        router.get('/cc-card', { sheet: selectedFilter, card: cardValue, search: searchQuery || undefined }, { 
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                setVisibleDestinationsCount(5);
            }
        });
        setIsCardDropdownOpen(false);
    };
    
    const handleDeleteSheet = async (sheetName, e) => {
        e.stopPropagation();
        
        const confirmation = prompt(
            `⚠️ WARNING: This will permanently delete ALL transactions in "${sheetName}"!\n\n` +
            `Type "${sheetName}" to confirm deletion:`,
            ''
        );
        
        if (confirmation !== sheetName) {
            if (confirmation !== null) {
                alert('Sheet name does not match. Deletion cancelled.');
            }
            return;
        }
        
        try {
            const response = await axios.delete('/cc-card/sheet/delete', {
                data: { sheet_name: sheetName }
            });
            
            alert(response.data.message + `\n${response.data.deleted_transactions} transactions deleted.`);
            window.location.reload();
        } catch (error) {
            console.error('Delete sheet failed:', error);
            alert('Failed to delete sheet: ' + (error.response?.data?.error || error.message));
        }
    };
    
    const getFilterLabel = () => {
        if (!availableFilters || availableFilters.length === 0) return 'All Sheets';
        const filter = availableFilters.find(f => f.value === selectedFilter);
        return filter ? filter.label : 'All Sheets';
    };
    
    const toggleYearCollapse = (year) => {
        setCollapsedYears(prev => ({
            ...prev,
            [year]: !prev[year]
        }));
    };
    return (
        <MainLayout>
            <Toaster position="top-right" />
            {/* Hero Header with Gradient */}
            <div className="bg-gradient-to-r from-blue-600 via-cyan-500 to-teal-500 text-white p-8 shadow-lg mb-8">
                <div className="max-w-7xl mx-auto">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="bg-white/20 backdrop-blur-sm p-4 rounded-2xl">
                                <svg className="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </div>
                            <div>
                                <h1 className="text-4xl font-extrabold tracking-tight">CC Card Monitoring</h1>
                                <p className="text-blue-100 mt-1 text-sm font-medium">Corporate Credit Card Transaction Management</p>
                            </div>
                        </div>
                        
                        <div className="flex gap-3">
                            <button
                                onClick={() => setIsModalOpen(true)}
                                className="px-5 py-3 bg-white/20 backdrop-blur-md hover:bg-white/30 rounded-xl font-semibold transition-all duration-300 flex items-center gap-2 shadow-lg hover:shadow-xl hover:scale-105"
                            >
                                <span className="text-2xl">➕</span>
                                <span>New Transaction</span>
                            </button>
                            <button
                                onClick={() => setIsDeleteAllModalOpen(true)}
                                className="px-5 py-3 bg-rose-500/80 backdrop-blur-md hover:bg-rose-600 rounded-xl font-semibold transition-all duration-300 flex items-center gap-2 shadow-lg hover:shadow-xl hover:scale-105"
                            >
                                <span className="text-2xl">🗑️</span>
                                <span>Hapus Semua</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="p-8 bg-gray-50 min-h-screen">
                <div className="max-w-7xl mx-auto">
                    {/* Enhanced Search & Filter Bar */}
                    <div className="bg-white rounded-2xl shadow-xl p-6 mb-8 border border-gray-100">
                        <div className="flex flex-wrap gap-4 items-center">
                            <div className="flex-1 min-w-[300px]">
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        value={searchQuery}
                                        onChange={handleSearchChange}
                                        placeholder="🔍 Search employee, booking ID..."
                                        className="w-full pl-12 pr-4 py-3 bg-cyan-50 border-2 border-cyan-200 rounded-xl focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100 transition-all"
                                    />
                                    {searchQuery && (
                                        <button
                                            onClick={handleClearSearch}
                                            className="absolute right-3 top-3 text-gray-500 hover:text-gray-700"
                                            title="Clear search"
                                        >
                                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    )}
                                </div>
                                    </div>
                            
                            {/* CC Card Filter Dropdown */}
                            <div className="relative">
                                <button 
                                    onClick={() => setIsCardDropdownOpen(!isCardDropdownOpen)}
                                    className="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white border-0 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all cursor-pointer focus:ring-4 focus:ring-green-200 flex items-center gap-2"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    <span>{selectedCard === 'all' ? 'All Cards' : selectedCard === '5657' ? 'CC 5657' : 'CC 9386'}</span>
                                    <svg className={`w-4 h-4 transition-transform ${isCardDropdownOpen ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                
                                {isCardDropdownOpen && (
                                    <div className="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl z-50 border-2 border-gray-100">
                                        <div className="py-1">
                                            <button
                                                onClick={() => handleCardFilterChange('all')}
                                                className={`w-full text-left px-4 py-2 hover:bg-green-50 transition rounded-lg ${selectedCard === 'all' ? 'bg-green-100 font-semibold' : ''}`}
                                            >
                                                💳 All Cards
                                            </button>
                                            <button
                                                onClick={() => handleCardFilterChange('5657')}
                                                className={`w-full text-left px-4 py-2 hover:bg-green-50 transition rounded-lg ${selectedCard === '5657' ? 'bg-green-100 font-semibold' : ''}`}
                                            >
                                                CC 5657
                                            </button>
                                            <button
                                                onClick={() => handleCardFilterChange('9386')}
                                                className={`w-full text-left px-4 py-2 hover:bg-green-50 transition rounded-lg ${selectedCard === '9386' ? 'bg-green-100 font-semibold' : ''}`}
                                            >
                                                CC 9386
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </div>
                            
                            {/* Year Filter Dropdown */}
                            <div className="relative">
                                <button 
                                    onClick={() => setIsYearDropdownOpen(!isYearDropdownOpen)}
                                    className="px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white border-0 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all cursor-pointer focus:ring-4 focus:ring-purple-200 flex items-center gap-2"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>{selectedFilter.startsWith('year:') ? selectedFilter.replace('year:', 'Year: ') : 'All Years'}</span>
                                    <svg className={`w-4 h-4 transition-transform ${isYearDropdownOpen ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                
                                {isYearDropdownOpen && (
                                    <div className="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl z-50 border-2 border-gray-100 max-h-64 overflow-y-auto">
                                        <div className="py-1">
                                            <button
                                                onClick={() => {
                                                    handleFilterChange('all');
                                                    setIsYearDropdownOpen(false);
                                                }}
                                                className={`w-full text-left px-4 py-2 hover:bg-purple-50 transition rounded-lg ${selectedFilter === 'all' ? 'bg-purple-100 font-semibold' : ''}`}
                                            >
                                                📅 All Years
                                            </button>
                                    {availableYears && availableYears.map(year => (
                                        <button
                                            key={year}
                                            onClick={() => {
                                                handleFilterChange(`year:${year}`);
                                                setIsYearDropdownOpen(false);
                                            }}
                                            className={`w-full text-left px-4 py-2 hover:bg-purple-50 transition rounded-lg ${selectedFilter === `year:${year}` ? 'bg-purple-100 font-semibold' : ''}`}
                                        >
                                            Year: {year}
                                        </button>
                                    ))}
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Sheet Filter Dropdown */}
                            <div className="relative">
                                <button 
                                    onClick={() => setIsFilterDropdownOpen(!isFilterDropdownOpen)}
                                    className="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-500 text-white border-0 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all cursor-pointer focus:ring-4 focus:ring-cyan-200 flex items-center gap-2"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span className="truncate">{getFilterLabel()}</span>
                                    <svg className={`w-4 h-4 transition-transform ${isFilterDropdownOpen ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                        
                        {isFilterDropdownOpen && (
                            <div className="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl z-50 border-2 border-gray-100 max-h-96 overflow-y-auto">
                                <div className="py-1">
                                    {availableFilters && availableFilters.length > 0 ? availableFilters.map((filter, index) => {
                                        // Skip year filters (now in separate dropdown)
                                        if (filter.type === 'year') {
                                            return null;
                                        }
                                        
                                        // Check if this sheet should be hidden due to collapsed year
                                        if (filter.type === 'sheet' && filter.year && collapsedYears[filter.year]) {
                                            return null;
                                        }
                                        
                                        return (
                                            <React.Fragment key={filter.value}>
                                                {filter.type === 'header' ? (
                                                    <button
                                                        onClick={() => toggleYearCollapse(filter.value.replace('header:', ''))}
                                                        className="w-full px-4 py-2 text-xs font-bold text-gray-700 uppercase bg-blue-50 border-t border-b border-blue-200 hover:bg-blue-100 transition flex items-center justify-between rounded-lg"
                                                    >
                                                        <span>📄 {filter.label}</span>
                                                        <svg 
                                                            className={`w-4 h-4 transition-transform ${collapsedYears[filter.value.replace('header:', '')] ? '' : 'rotate-90'}`} 
                                                            fill="none" 
                                                            stroke="currentColor" 
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                                        </svg>
                                                    </button>
                                                ) : filter.type === 'sheet' && filter.value !== 'all' ? (
                                                    <div className={`flex items-center justify-between px-4 py-2 hover:bg-cyan-50 transition text-sm group rounded-lg ${selectedFilter === filter.value ? 'bg-cyan-100 font-semibold' : ''}`}>
                                                        <button onClick={() => handleFilterChange(filter.value)} className="flex-1 text-left">
                                                            {filter.label}
                                                        </button>
                                                        <button onClick={(e) => handleDeleteSheet(filter.value, e)} className="px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 transition opacity-0 group-hover:opacity-100" title="Delete entire sheet">
                                                            Delete
                                                        </button>
                                                    </div>
                                                ) : filter.type !== 'header' && filter.type !== 'year' ? (
                                                    <button onClick={() => handleFilterChange(filter.value)} className={`w-full text-left px-4 py-2 hover:bg-cyan-50 transition text-sm rounded-lg ${selectedFilter === filter.value ? 'bg-cyan-100 font-semibold' : ''}`}>
                                                        {filter.type === 'all' && '📊 '}
                                                        {filter.label}
                                                    </button>
                                                ) : null}
                                            </React.Fragment>
                                        );
                                    }) : (
                                        <div className="px-4 py-2 text-sm text-gray-500">No filters available</div>
                                    )}

                                </div>
                            </div>
                        )}
                            </div>
                        </div>
                    </div>

                    {/* Summary Cards with Modern Design */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        {/* Total Payment Card */}
                        <div className="group relative overflow-hidden bg-gradient-to-br from-blue-500 via-blue-600 to-cyan-600 rounded-3xl shadow-2xl hover:shadow-3xl transition-all duration-500 hover:scale-105">
                            <div className="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -mr-20 -mt-20 group-hover:scale-150 transition-transform duration-700"></div>
                            <div className="relative p-6 text-white">
                                <div className="flex items-start justify-between mb-3">
                                    <div className="bg-white/20 backdrop-blur-sm p-3 rounded-2xl group-hover:rotate-12 transition-transform duration-500">
                                        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-xs font-medium text-blue-100 mb-1">Total Payment</div>
                                        <div className="text-2xl font-black tracking-tight">
                                            Rp {formatSummaryDisplay(totalPayment).value}{formatSummaryDisplay(totalPayment).unit}
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-3 pt-3 border-t border-white/20">
                                    <div className="text-xs text-blue-50">{formatRupiah(totalPayment)}</div>
                                </div>
                            </div>
                        </div>

                        {/* Total Biaya Adm & Bunga Card */}
                        <div className="group relative overflow-hidden bg-gradient-to-br from-yellow-500 via-orange-500 to-red-500 rounded-3xl shadow-2xl hover:shadow-3xl transition-all duration-500 hover:scale-105">
                            <div className="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -mr-20 -mt-20 group-hover:scale-150 transition-transform duration-700"></div>
                            <div className="relative p-6 text-white">
                                <div className="flex items-start justify-between mb-3">
                                    <div className="bg-white/20 backdrop-blur-sm p-3 rounded-2xl group-hover:rotate-12 transition-transform duration-500">
                                        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-xs font-medium text-yellow-100 mb-1">Biaya Adm & Bunga</div>
                                        <div className="text-2xl font-black tracking-tight">
                                            Rp {formatSummaryDisplay(totalAdminInterest).value}{formatSummaryDisplay(totalAdminInterest).unit}
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-3 pt-3 border-t border-white/20">
                                    <div className="text-xs text-yellow-50">{formatRupiah(totalAdminInterest)}</div>
                                </div>
                            </div>
                        </div>

                        {/* Total Refund Card */}
                        <div className="group relative overflow-hidden bg-gradient-to-br from-pink-500 via-rose-600 to-red-600 rounded-3xl shadow-2xl hover:shadow-3xl transition-all duration-500 hover:scale-105">
                            <div className="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -mr-20 -mt-20 group-hover:scale-150 transition-transform duration-700"></div>
                            <div className="relative p-6 text-white">
                                <div className="flex items-start justify-between mb-3">
                                    <div className="bg-white/20 backdrop-blur-sm p-3 rounded-2xl group-hover:rotate-12 transition-transform duration-500">
                                        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                        </svg>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-xs font-medium text-pink-100 mb-1">Total Refund</div>
                                        <div className="text-2xl font-black tracking-tight">
                                            Rp {formatSummaryDisplay(totalRefund).value}{formatSummaryDisplay(totalRefund).unit}
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-3 pt-3 border-t border-white/20">
                                    <div className="text-xs text-pink-50">{formatRupiah(totalRefund)}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                {/* Main Content Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Chart with Mode Toggle */}
                    <div className="bg-white rounded-xl p-6 shadow-lg">
                        <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 gap-3">
                            <h3 className="text-lg font-bold">
                                {chartMode === 'monthly' ? 'Monthly Overview' : 'Sheet Comparison'}
                            </h3>
                            
                            <div className="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                                {/* Chart Mode Toggle */}
                                <div className="flex items-center gap-1 sm:gap-2 bg-gray-100 rounded-lg p-1">
                                    <button
                                        onClick={() => setChartMode('monthly')}
                                        className={`px-3 sm:px-4 py-1.5 rounded-md text-xs sm:text-sm font-medium transition ${
                                            chartMode === 'monthly' 
                                                ? 'bg-cyan-500 text-white shadow-sm' 
                                                : 'text-gray-600 hover:text-gray-900'
                                        }`}
                                    >
                                        Monthly
                                    </button>
                                    <button
                                        onClick={() => setChartMode('comparison')}
                                        className={`px-3 sm:px-4 py-1.5 rounded-md text-xs sm:text-sm font-medium transition ${
                                            chartMode === 'comparison' 
                                                ? 'bg-cyan-500 text-white shadow-sm' 
                                                : 'text-gray-600 hover:text-gray-900'
                                        }`}
                                    >
                                        Comparison
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        {/* Conditional Chart Rendering */}
                        {chartMode === 'monthly' ? (
                            (() => {
                                // Show all monthly chart data without year filtering
                                const filteredChartData = monthlyChartData;
                                
                                // Monthly Chart (single sheet atau multiple sheets)
                                return filteredChartData && filteredChartData.length > 0 ? (
                                    <ResponsiveContainer width="100%" height={300} className="sm:h-[350px] lg:h-[400px]">
                                        <BarChart data={filteredChartData}>
                                        <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                        <XAxis 
                                            dataKey="sheet" 
                                            tick={{ fill: '#6b7280', fontSize: 10 }}
                                            angle={filteredChartData.length > 1 ? -15 : 0}
                                            textAnchor={filteredChartData.length > 1 ? "end" : "middle"}
                                            height={filteredChartData.length > 1 ? 60 : 30}
                                        />
                                        <YAxis 
                                            tickFormatter={(value) => `${value}M`}
                                            tick={{ fill: '#6b7280', fontSize: 10 }}
                                        />
                                        <Tooltip content={<CustomTooltip />} />
                                        <Legend 
                                            wrapperStyle={{ paddingTop: '10px', fontSize: '12px' }}
                                            iconType="circle"
                                        />
                                        <Bar 
                                            dataKey="payment" 
                                            fill="#22c55e" 
                                            radius={[8, 8, 0, 0]} 
                                            name="Payment"
                                            barSize={monthlyChartData.length === 1 ? 80 : undefined}
                                        />
                                        <Bar 
                                            dataKey="refund" 
                                            fill="#ef4444" 
                                            radius={[8, 8, 0, 0]} 
                                            name="Refund"
                                            barSize={filteredChartData.length === 1 ? 80 : undefined}
                                        />
                                    </BarChart>
                                </ResponsiveContainer>
                                ) : (
                                    <div className="h-64 sm:h-80 lg:h-96 flex items-center justify-center text-gray-500">
                                        <div className="text-center">
                                            <svg className="w-12 h-12 sm:w-16 sm:h-16 mx-auto mb-3 sm:mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                            </svg>
                                            <p className="font-semibold mb-1 text-sm sm:text-base">Tidak ada data</p>
                                            <p className="text-xs sm:text-sm">Data tidak tersedia</p>
                                        </div>
                                    </div>
                                );
                            })()
                        ) : (
                            // Comparison Chart (semua sheet)
                            sheetComparison && sheetComparison.length > 0 ? (
                                <ResponsiveContainer width="100%" height={400}>
                                    <BarChart data={sheetComparison}>
                                        <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                        <XAxis 
                                            dataKey="sheet" 
                                            tick={{ fill: '#6b7280', fontSize: 11 }}
                                            angle={-15}
                                            textAnchor="end"
                                            height={60}
                                        />
                                        <YAxis 
                                            tickFormatter={(value) => `${value}M`}
                                            tick={{ fill: '#6b7280', fontSize: 12 }}
                                        />
                                        <Tooltip content={<CustomTooltip />} />
                                        <Legend 
                                            wrapperStyle={{ paddingTop: '20px' }}
                                            iconType="circle"
                                        />
                                        <Bar 
                                            dataKey="payment" 
                                            fill="#22c55e" 
                                            radius={[8, 8, 0, 0]} 
                                            name="Payment"
                                        />
                                        <Bar 
                                            dataKey="refund" 
                                            fill="#ef4444" 
                                            radius={[8, 8, 0, 0]} 
                                            name="Refund"
                                        />
                                    </BarChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="h-96 flex items-center justify-center text-gray-500">
                                    No data available
                                </div>
                            )
                        )}
                    </div>

                    {/* Destinations List - Show 5 initially, load +10 more each time, scrollable within card */}
                    <div className="bg-white rounded-lg p-4 sm:p-6 shadow flex flex-col max-h-[500px] sm:max-h-[540px]">
                        <div className="mb-3 sm:mb-4 flex-shrink-0">
                            <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 sm:gap-0 mb-2">
                                <h3 className="text-base sm:text-lg font-bold">Transactions</h3>
                                
                                {/* Transaction Type Filter */}
                                <div className="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                                <button
                                    onClick={() => {
                                        setTransactionTypeFilter('payment');
                                        setVisibleDestinationsCount(5);
                                    }}
                                    className={`px-3 py-1 rounded-md text-xs font-medium transition ${
                                        transactionTypeFilter === 'payment' 
                                            ? 'bg-green-500 text-white shadow-sm' 
                                            : 'text-gray-600 hover:text-gray-900'
                                    }`}
                                >
                                    Payment
                                </button>
                                <button
                                    onClick={() => {
                                        setTransactionTypeFilter('refund');
                                        setVisibleDestinationsCount(5);
                                    }}
                                    className={`px-3 py-1 rounded-md text-xs font-medium transition ${
                                        transactionTypeFilter === 'refund' 
                                            ? 'bg-red-500 text-white shadow-sm' 
                                            : 'text-gray-600 hover:text-gray-900'
                                    }`}
                                >
                                    Refund
                                </button>
                                <button
                                    onClick={() => {
                                        setTransactionTypeFilter('all');
                                        setVisibleDestinationsCount(5);
                                    }}
                                    className={`px-3 py-1 rounded-md text-xs font-medium transition ${
                                        transactionTypeFilter === 'all' 
                                            ? 'bg-cyan-500 text-white shadow-sm' 
                                            : 'text-gray-600 hover:text-gray-900'
                                    }`}
                                >
                                    All
                                </button>
                            </div>
                            </div>
                            
                            {/* Search Results Info */}
                            {searchQuery && (
                                <div className="text-xs sm:text-sm text-gray-600 mb-2">
                                    {filteredDestinations.length > 0 ? (
                                        <span>Found <strong>{filteredDestinations.length}</strong> result{filteredDestinations.length !== 1 ? 's' : ''} for "<strong>{searchQuery}</strong>"</span>
                                    ) : (
                                        <span>No results found for "<strong>{searchQuery}</strong>"</span>
                                    )}
                                </div>
                            )}
                        </div>
                        
                        {/* Scrollable Content Area */}
                        <div className="flex-1 overflow-y-auto pr-1 sm:pr-2 space-y-2 sm:space-y-3 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                            {filteredDestinations && filteredDestinations.length > 0 ? (
                                <>
                                    {filteredDestinations.slice(0, visibleDestinationsCount).map((dest, index) => (
                                        <div key={index} className={`rounded-lg p-3 sm:p-4 hover:bg-opacity-80 transition ${
                                            dest.type === 'refund' ? 'bg-red-50' : 'bg-gray-100'
                                        }`}>
                                            <div className="flex justify-between items-start mb-2">
                                                <div className="flex-1 min-w-0 pr-2">
                                                    <p className="font-semibold text-gray-900 text-sm sm:text-base truncate">{dest.route}</p>
                                                    <p className="text-xs sm:text-sm text-gray-600">{dest.trips}</p>
                                                </div>
                                                <p className={`text-base sm:text-xl font-bold flex-shrink-0 ${
                                                    dest.type === 'refund' ? 'text-red-600' : 'text-green-600'
                                                }`}>{dest.amount}</p>
                                            </div>
                                            <button
                                                onClick={() => {
                                                    // Check if this is a refund item
                                                    if (dest.type === 'refund') {
                                                        // For refunds, navigate to refund-detail with employee name
                                                        const params = {
                                                            employee: dest.employee_name || dest.route
                                                        };
                                                        
                                                        // If year filter is selected, pass year parameter
                                                        if (selectedFilter.startsWith('year:')) {
                                                            params.year = selectedFilter.replace('year:', '');
                                                        } else if (selectedFilter !== 'all') {
                                                            // If sheet filter is selected, pass sheet parameter
                                                            params.sheet = selectedFilter;
                                                        }
                                                        
                                                        router.get('/cc-card/refund-detail', params);
                                                    } else {
                                                        // For payments, navigate to destination-detail
                                                        const params = {
                                                            destination: dest.route,
                                                            type: 'payment'
                                                        };
                                                        
                                                        // If year filter is selected, pass year parameter
                                                        if (selectedFilter.startsWith('year:')) {
                                                            params.year = selectedFilter.replace('year:', '');
                                                        } else if (selectedFilter !== 'all') {
                                                            // If sheet filter is selected, pass sheet parameter
                                                            params.sheet = selectedFilter;
                                                        }
                                                        
                                                        router.get('/cc-card/destination-detail', params);
                                                    }
                                                }}
                                                className="w-full mt-2 px-3 sm:px-4 py-1.5 bg-cyan-500 hover:bg-cyan-600 text-white text-xs sm:text-sm rounded-md transition font-medium"
                                            >
                                                Lihat Detail
                                            </button>
                                        </div>
                                    ))}
                                </>
                            ) : (
                                <div className="text-center text-gray-500 py-6 sm:py-8">
                                    {searchQuery ? (
                                        <div>
                                            <svg className="w-12 h-12 sm:w-16 sm:h-16 mx-auto mb-3 sm:mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                            <p className="font-medium text-sm sm:text-base">No destinations found</p>
                                            <p className="text-xs sm:text-sm mt-1">Try a different search term</p>
                                        </div>
                                    ) : (
                                        <p className="text-sm sm:text-base">Tidak ada data destinasi</p>
                                    )}
                                </div>
                            )}
                        </div>
                        
                        {/* Load More Button - Fixed at bottom */}
                        {filteredDestinations.length > visibleDestinationsCount && (
                            <div className="mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-200 flex-shrink-0">
                                <button
                                    onClick={() => setVisibleDestinationsCount(prev => Math.min(prev + 10, filteredDestinations.length))}
                                    className="w-full px-3 sm:px-4 py-2 sm:py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition font-semibold flex items-center justify-center gap-2 text-xs sm:text-sm"
                                >
                                    <svg className="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                    <span className="hidden sm:inline">Muat Lebih Banyak (+10 lagi - {filteredDestinations.length - visibleDestinationsCount} tersisa)</span>
                                    <span className="sm:hidden">Load More (+10)</span>
                                </button>
                            </div>
                        )}
                    </div>
                </div>

                {/* Analytics Section */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mt-4 sm:mt-6">
                    {/* Payment vs Refund Pie Chart */}
                    <div className="bg-white rounded-lg p-4 sm:p-6 shadow">
                        <h3 className="text-base sm:text-lg font-bold mb-3 sm:mb-4">Payment vs Refund Ratio</h3>
                        {paymentRefundRatio.payment + paymentRefundRatio.refund > 0 ? (
                            <div className="flex flex-col items-center">
                                <ResponsiveContainer width="100%" height={180} className="sm:h-[200px]">
                                    <PieChart>
                                        <Pie
                                            data={[
                                                { name: 'Payment', value: paymentRefundRatio.payment },
                                                { name: 'Refund', value: paymentRefundRatio.refund }
                                            ]}
                                            cx="50%"
                                            cy="50%"
                                            labelLine={false}
                                            label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(1)}%`}
                                            outerRadius={70}
                                            fill="#8884d8"
                                            dataKey="value"
                                            style={{ fontSize: '11px' }}
                                        >
                                            <Cell fill="#10b981" />
                                            <Cell fill="#ef4444" />
                                        </Pie>
                                        <Tooltip 
                                            formatter={(value) => `Rp ${value}M`}
                                            contentStyle={{ fontSize: '12px' }}
                                        />
                                    </PieChart>
                                </ResponsiveContainer>
                                <div className="grid grid-cols-2 gap-3 sm:gap-4 w-full mt-3 sm:mt-4">
                                    <div className="text-center">
                                        <div className="flex items-center justify-center gap-1 sm:gap-2 mb-1">
                                            <div className="w-2 h-2 sm:w-3 sm:h-3 bg-green-500 rounded-full"></div>
                                            <p className="text-xs sm:text-sm font-medium text-gray-600">Payment</p>
                                        </div>
                                        <p className="text-base sm:text-lg font-bold text-green-600">Rp {paymentRefundRatio.payment}M</p>
                                        <p className="text-[10px] sm:text-xs text-gray-500">{paymentRefundRatio.paymentPercentage}%</p>
                                    </div>
                                    <div className="text-center">
                                        <div className="flex items-center justify-center gap-1 sm:gap-2 mb-1">
                                            <div className="w-2 h-2 sm:w-3 sm:h-3 bg-red-500 rounded-full"></div>
                                            <p className="text-xs sm:text-sm font-medium text-gray-600">Refund</p>
                                        </div>
                                        <p className="text-base sm:text-lg font-bold text-red-600">Rp {paymentRefundRatio.refund}M</p>
                                        <p className="text-[10px] sm:text-xs text-gray-500">{paymentRefundRatio.refundPercentage}%</p>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div className="h-48 sm:h-64 flex items-center justify-center text-gray-500 text-sm">
                                No data available
                            </div>
                        )}
                    </div>

                    {/* Top 10 Employees by Transaction Count */}
                    <div className="bg-white rounded-lg p-4 sm:p-6 shadow">
                        <h3 className="text-base sm:text-lg font-bold mb-3 sm:mb-4">Top 10 Employees (Trips)</h3>
                        <div className="space-y-1.5 sm:space-y-2 max-h-[280px] sm:max-h-[320px] overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300">
                            {topEmployeesByCount && topEmployeesByCount.length > 0 ? (
                                topEmployeesByCount.map((employee, index) => (
                                    <div key={index} className="flex items-center justify-between p-1.5 sm:p-2 hover:bg-gray-50 rounded">
                                        <div className="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                                            <div className={`flex-shrink-0 w-5 h-5 sm:w-6 sm:h-6 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold ${
                                                index === 0 ? 'bg-yellow-400 text-yellow-900' :
                                                index === 1 ? 'bg-gray-300 text-gray-700' :
                                                index === 2 ? 'bg-orange-400 text-orange-900' :
                                                'bg-gray-200 text-gray-600'
                                            }`}>
                                                {index + 1}
                                            </div>
                                            <p className="text-xs sm:text-sm font-medium text-gray-900 truncate">{employee.name}</p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <span className="text-xs sm:text-sm font-bold text-cyan-600">{employee.count} trips</span>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <p className="text-center text-gray-500 py-4 text-sm">No data</p>
                            )}
                        </div>
                    </div>

                    {/* Top 10 Employees by Amount */}
                    <div className="bg-white rounded-lg p-4 sm:p-6 shadow">
                        <h3 className="text-base sm:text-lg font-bold mb-3 sm:mb-4">Top 10 Employees (Amount)</h3>
                        <div className="space-y-1.5 sm:space-y-2 max-h-[280px] sm:max-h-[320px] overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300">
                            {topEmployeesByAmount && topEmployeesByAmount.length > 0 ? (
                                topEmployeesByAmount.map((employee, index) => (
                                    <div key={index} className="flex items-center justify-between p-1.5 sm:p-2 hover:bg-gray-50 rounded">
                                        <div className="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                                            <div className={`flex-shrink-0 w-5 h-5 sm:w-6 sm:h-6 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold ${
                                                index === 0 ? 'bg-yellow-400 text-yellow-900' :
                                                index === 1 ? 'bg-gray-300 text-gray-700' :
                                                index === 2 ? 'bg-orange-400 text-orange-900' :
                                                'bg-gray-200 text-gray-600'
                                            }`}>
                                                {index + 1}
                                            </div>
                                            <p className="text-xs sm:text-sm font-medium text-gray-900 truncate">{employee.name}</p>
                                        </div>
                                        <div className="flex flex-col items-end">
                                            <span className="text-xs sm:text-sm font-bold text-green-600">
                                                {employee.total}
                                            </span>
                                            <span className="text-[10px] sm:text-xs text-gray-500">{employee.count} trips</span>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <p className="text-center text-gray-500 py-4">No data</p>
                            )}
                        </div>
                    </div>
                </div>
                </div>
            </div>
            
            {/* New Transaction Modal */}
            <NewTransactionModal 
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                availableSheets={availableFilters ? availableFilters.filter(f => f.type === 'sheet' && f.value !== 'all').map(f => f.value) : []}
            />
            
            {/* Delete All Modal */}
            <DeleteAllCCCardModal
                isOpen={isDeleteAllModalOpen}
                onClose={() => setIsDeleteAllModalOpen(false)}
                totalRecords={totalTransactions}
            />
        </MainLayout>
    );
}
