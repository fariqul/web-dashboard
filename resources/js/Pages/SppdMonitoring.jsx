import React, { useState, useEffect } from 'react';
import MainLayout from '../Layouts/MainLayout';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, ResponsiveContainer, Tooltip, Legend, PieChart, Pie, Cell } from 'recharts';
import { router } from '@inertiajs/react';
import ImportSppdModal from '../Components/ImportSppdModal';
import AddSppdModal from '../Components/AddSppdModal';
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

export default function SppdMonitoring({ 
    totalPaidAmount = 0,
    totalTrips = 0,
    averageAmount = 0,
    uniqueCustomers = 0,
    reasons = [],
    availableFilters = [],
    availableReasons = [],
    availableBanks = [],
    selectedFilter = 'all',
    selectedReason = 'all',
    selectedBank = 'all',
    selectedStatus = 'all',
    paymentChartData = null,
    tripChartData = null,
    topCustomersByCount = [],
    topCustomersByAmount = [],
    popularDestinations = [],
    monthlyOverviewData = [],
    tripsByReason = { all: [], upcoming: [], ongoing: [], completed: [] },
    statusCounts = { upcoming: 0, ongoing: 0, completed: 0 },
    statusAmounts = { upcoming: 0, ongoing: 0, completed: 0 },
    flash = {}
}) {
    const [isFilterDropdownOpen, setIsFilterDropdownOpen] = useState(false);
    const [isStatusDropdownOpen, setIsStatusDropdownOpen] = useState(false);
    const [isReasonDropdownOpen, setIsReasonDropdownOpen] = useState(false);
    const [isBankDropdownOpen, setIsBankDropdownOpen] = useState(false);
    const [isImportModalOpen, setIsImportModalOpen] = useState(false);
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);
    const [chartViewMode, setChartViewMode] = useState('monthly'); // 'monthly' or 'status'
    const [customerViewMode, setCustomerViewMode] = useState('trips'); // 'trips' or 'amount'
    const [reasonsStatusFilter, setReasonsStatusFilter] = useState('all'); // For Trips by Reason section
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
    
    // Handle search with debounce
    const handleSearchChange = (e) => {
        const value = e.target.value;
        setSearchQuery(value);
        
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        const timeout = setTimeout(() => {
            router.get('/sppd', { 
                sheet: selectedFilter,
                status: selectedStatus !== 'all' ? selectedStatus : undefined,
                reason: selectedReason !== 'all' ? selectedReason : undefined,
                bank: selectedBank !== 'all' ? selectedBank : undefined,
                search: value 
            }, { 
                preserveState: true,
                preserveScroll: true
            });
        }, 500);
        
        setSearchTimeout(timeout);
    };
    
    const handleClearSearch = () => {
        setSearchQuery('');
        router.get('/sppd', { 
            sheet: selectedFilter,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            reason: selectedReason !== 'all' ? selectedReason : undefined,
            bank: selectedBank !== 'all' ? selectedBank : undefined
        }, { 
            preserveState: true,
            preserveScroll: true
        });
    };
    
    const handleFilterChange = (filterValue) => {
        router.get('/sppd', { 
            sheet: filterValue, 
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            reason: selectedReason !== 'all' ? selectedReason : undefined,
            bank: selectedBank !== 'all' ? selectedBank : undefined,
            search: searchQuery || undefined 
        }, { 
            preserveState: true,
            preserveScroll: true
        });
        setIsFilterDropdownOpen(false);
    };
    
    const handleStatusChange = (statusValue) => {
        router.get('/sppd', { 
            sheet: selectedFilter,
            status: statusValue !== 'all' ? statusValue : undefined,
            reason: selectedReason !== 'all' ? selectedReason : undefined,
            bank: selectedBank !== 'all' ? selectedBank : undefined,
            search: searchQuery || undefined 
        }, { 
            preserveState: true,
            preserveScroll: true
        });
        setIsStatusDropdownOpen(false);
    };
    
    const getFilterLabel = () => {
        if (!availableFilters || availableFilters.length === 0) return 'All Months';
        const filter = availableFilters.find(f => f.value === selectedFilter);
        return filter ? filter.label : 'All Months';
    };
    
    const getStatusLabel = () => {
        const labels = {
            'all': 'All Status',
            'upcoming': 'Upcoming',
            'ongoing': 'Ongoing',
            'completed': 'Completed'
        };
        return labels[selectedStatus] || 'All Status';
    };
    
    const handleDeleteSheet = async () => {
        if (selectedFilter === 'all' || selectedFilter.startsWith('year:')) {
            toast.error('Please select a specific sheet to delete');
            return;
        }
        
        if (!confirm(`Are you sure you want to delete all trips in sheet "${selectedFilter}"? This action cannot be undone.`)) {
            return;
        }
        
        try {
            await axios.delete('/sppd/sheet/delete', {
                data: {
                    sheet_name: selectedFilter
                }
            });
            toast.success('Sheet deleted successfully!');
            router.reload();
        } catch (error) {
            toast.error('Failed to delete sheet');
        }
    };
    
    const totalPaidDisplay = formatSummaryDisplay(totalPaidAmount);
    
    // Status distribution data for pie chart
    const statusDistributionData = [
        { name: 'Completed', value: statusCounts.completed, color: '#9ca3af' },
        { name: 'Ongoing', value: statusCounts.ongoing, color: '#22c55e' },
        { name: 'Upcoming', value: statusCounts.upcoming, color: '#3b82f6' },
    ].filter(item => item.value > 0);
    
    // Filter trips by reason based on local status filter
    const filteredTripsByReason = tripsByReason[reasonsStatusFilter] || tripsByReason.all || [];

    return (
        <MainLayout>
            <Toaster position="top-right" />
            <ImportSppdModal 
                isOpen={isImportModalOpen} 
                onClose={() => setIsImportModalOpen(false)} 
            />
            <AddSppdModal
                isOpen={isAddModalOpen}
                onClose={() => setIsAddModalOpen(false)}
            />
            
            {/* Hero Header with Gradient */}
            <div className="bg-gradient-to-r from-purple-600 via-pink-500 to-rose-500 text-white p-8 shadow-lg">
                <div className="max-w-7xl mx-auto">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="bg-white/20 backdrop-blur-sm p-4 rounded-2xl">
                                <svg className="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <h1 className="text-4xl font-extrabold tracking-tight">SPPD</h1>
                                <p className="text-pink-100 mt-1 text-sm font-medium">Surat Perintah Perjalanan Dinas</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            {selectedFilter !== 'all' && !selectedFilter.startsWith('year:') && (
                                <button
                                    onClick={handleDeleteSheet}
                                    className="px-5 py-3 bg-red-500/80 hover:bg-red-600 backdrop-blur-sm border-2 border-red-400/50 text-white rounded-xl font-semibold transition flex items-center gap-2"
                                >
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete Sheet
                                </button>
                            )}
                            <button
                                onClick={() => setIsImportModalOpen(true)}
                                className="px-5 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm border-2 border-white/50 text-white rounded-xl font-semibold transition flex items-center gap-2"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Import CSV/Excel
                            </button>
                            <button
                                onClick={() => setIsAddModalOpen(true)}
                                className="px-5 py-3 bg-white text-purple-600 hover:bg-purple-50 rounded-xl font-semibold transition flex items-center gap-2 shadow-lg"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                </svg>
                                New Trip
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="p-8 bg-gray-50 min-h-screen">
                <div className="max-w-7xl mx-auto">
                    {/* Search & Filter Bar */}
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
                                        placeholder="Search trip number, customer, destination..."
                                        className="w-full pl-12 pr-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-100 transition-all"
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

                            {/* Month Filter Dropdown */}
                            <div className="relative">
                                <button 
                                    onClick={() => setIsFilterDropdownOpen(!isFilterDropdownOpen)}
                                    className="px-6 py-3 bg-cyan-500 text-white border-0 rounded-xl font-semibold shadow-lg hover:bg-cyan-600 transition-all cursor-pointer flex items-center gap-2"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span className="truncate">{getFilterLabel()}</span>
                                    <svg className={`w-4 h-4 transition-transform ${isFilterDropdownOpen ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                        
                                {isFilterDropdownOpen && (
                                    <div className="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl z-50 border-2 border-gray-100 max-h-96 overflow-y-auto">
                                        <div className="py-1">
                                            {availableFilters && availableFilters.map((filter, index) => (
                                                <button
                                                    key={index}
                                                    onClick={() => handleFilterChange(filter.value)}
                                                    className={`w-full text-left px-4 py-2 hover:bg-cyan-50 transition ${selectedFilter === filter.value ? 'bg-cyan-100 font-semibold' : ''}`}
                                                >
                                                    {filter.type === 'year' ? '📅 ' : filter.type === 'sheet' ? '📄 ' : '📊 '}{filter.label}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Status Filter Dropdown */}
                            <div className="relative">
                                <button 
                                    onClick={() => setIsStatusDropdownOpen(!isStatusDropdownOpen)}
                                    className="px-6 py-3 bg-green-500 text-white border-0 rounded-xl font-semibold shadow-lg hover:bg-green-600 transition-all cursor-pointer flex items-center gap-2"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{getStatusLabel()}</span>
                                    <svg className={`w-4 h-4 transition-transform ${isStatusDropdownOpen ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                        
                                {isStatusDropdownOpen && (
                                    <div className="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl z-50 border-2 border-gray-100">
                                        <div className="py-1">
                                            <button onClick={() => handleStatusChange('all')} className={`w-full text-left px-4 py-2 hover:bg-green-50 transition ${selectedStatus === 'all' ? 'bg-green-100 font-semibold' : ''}`}>
                                                📊 All Status
                                            </button>
                                            <button onClick={() => handleStatusChange('upcoming')} className={`w-full text-left px-4 py-2 hover:bg-blue-50 transition ${selectedStatus === 'upcoming' ? 'bg-blue-100 font-semibold' : ''}`}>
                                                🔵 Upcoming
                                            </button>
                                            <button onClick={() => handleStatusChange('ongoing')} className={`w-full text-left px-4 py-2 hover:bg-green-50 transition ${selectedStatus === 'ongoing' ? 'bg-green-100 font-semibold' : ''}`}>
                                                🟢 Ongoing
                                            </button>
                                            <button onClick={() => handleStatusChange('completed')} className={`w-full text-left px-4 py-2 hover:bg-gray-50 transition ${selectedStatus === 'completed' ? 'bg-gray-100 font-semibold' : ''}`}>
                                                ⚫ Completed
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Reason Filter Dropdown */}
                            <div className="relative">
                                <button 
                                    onClick={() => setIsReasonDropdownOpen(!isReasonDropdownOpen)}
                                    className="px-6 py-3 bg-purple-500 text-white border-0 rounded-xl font-semibold shadow-lg hover:bg-purple-600 transition-all cursor-pointer flex items-center gap-2"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    <span className="truncate">{selectedReason === 'all' ? 'All Reasons' : selectedReason}</span>
                                    <svg className={`w-4 h-4 transition-transform ${isReasonDropdownOpen ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                        
                                {isReasonDropdownOpen && (
                                    <div className="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl z-50 border-2 border-gray-100 max-h-96 overflow-y-auto">
                                        <div className="py-1">
                                            <button
                                                onClick={() => {
                                                    router.get('/sppd', { 
                                                        sheet: selectedFilter,
                                                        status: selectedStatus !== 'all' ? selectedStatus : undefined,
                                                        reason: undefined,
                                                        bank: selectedBank !== 'all' ? selectedBank : undefined,
                                                        search: searchQuery || undefined 
                                                    }, { preserveState: true, preserveScroll: true });
                                                    setIsReasonDropdownOpen(false);
                                                }}
                                                className={`w-full text-left px-4 py-2 hover:bg-purple-50 transition ${selectedReason === 'all' ? 'bg-purple-100 font-semibold' : ''}`}
                                            >
                                                📊 All Reasons
                                            </button>
                                            {availableReasons && availableReasons.map((reason, index) => (
                                                <button
                                                    key={index}
                                                    onClick={() => {
                                                        router.get('/sppd', { 
                                                            sheet: selectedFilter,
                                                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                                                            reason: reason,
                                                            bank: selectedBank !== 'all' ? selectedBank : undefined,
                                                            search: searchQuery || undefined 
                                                        }, { preserveState: true, preserveScroll: true });
                                                        setIsReasonDropdownOpen(false);
                                                    }}
                                                    className={`w-full text-left px-4 py-2 hover:bg-purple-50 transition ${selectedReason === reason ? 'bg-purple-100 font-semibold' : ''}`}
                                                >
                                                    🏷️ {reason}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Bank Filter Dropdown */}
                            <div className="relative">
                                <button 
                                    onClick={() => setIsBankDropdownOpen(!isBankDropdownOpen)}
                                    className="px-6 py-3 bg-amber-500 text-white border-0 rounded-xl font-semibold shadow-lg hover:bg-amber-600 transition-all cursor-pointer flex items-center gap-2"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span className="truncate">{selectedBank === 'all' ? 'All Banks' : selectedBank}</span>
                                    <svg className={`w-4 h-4 transition-transform ${isBankDropdownOpen ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                        
                                {isBankDropdownOpen && (
                                    <div className="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl z-50 border-2 border-gray-100 max-h-96 overflow-y-auto">
                                        <div className="py-1">
                                            <button
                                                onClick={() => {
                                                    router.get('/sppd', { 
                                                        sheet: selectedFilter,
                                                        status: selectedStatus !== 'all' ? selectedStatus : undefined,
                                                        reason: selectedReason !== 'all' ? selectedReason : undefined,
                                                        bank: undefined,
                                                        search: searchQuery || undefined 
                                                    }, { preserveState: true, preserveScroll: true });
                                                    setIsBankDropdownOpen(false);
                                                }}
                                                className={`w-full text-left px-4 py-2 hover:bg-amber-50 transition ${selectedBank === 'all' ? 'bg-amber-100 font-semibold' : ''}`}
                                            >
                                                🏦 All Banks
                                            </button>
                                            {availableBanks && availableBanks.map((bank, index) => (
                                                <button
                                                    key={index}
                                                    onClick={() => {
                                                        router.get('/sppd', { 
                                                            sheet: selectedFilter,
                                                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                                                            reason: selectedReason !== 'all' ? selectedReason : undefined,
                                                            bank: bank,
                                                            search: searchQuery || undefined 
                                                        }, { preserveState: true, preserveScroll: true });
                                                        setIsBankDropdownOpen(false);
                                                    }}
                                                    className={`w-full text-left px-4 py-2 hover:bg-amber-50 transition ${selectedBank === bank ? 'bg-amber-100 font-semibold' : ''}`}
                                                >
                                                    🏦 {bank}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Summary Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        {/* Total Trips Card */}
                        <div className="group relative overflow-hidden bg-gradient-to-br from-blue-500 via-cyan-500 to-teal-500 rounded-3xl shadow-2xl hover:shadow-3xl transition-all duration-500 hover:scale-105">
                            <div className="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -mr-20 -mt-20"></div>
                            <div className="relative p-6 text-white">
                                <div className="flex items-start justify-between mb-3">
                                    <div className="bg-white/20 backdrop-blur-sm p-3 rounded-2xl">
                                        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-xs font-medium text-cyan-100 mb-1">Total Trips</div>
                                        <div className="text-4xl font-black tracking-tight">{totalTrips}</div>
                                    </div>
                                </div>
                                <div className="mt-3 pt-3 border-t border-white/20 text-xs text-cyan-100">
                                    Upcoming: {statusCounts.upcoming} | Ongoing: {statusCounts.ongoing} | Completed: {statusCounts.completed}
                                </div>
                            </div>
                        </div>

                        {/* Total Paid Amount Card */}
                        <div className="group relative overflow-hidden bg-gradient-to-br from-orange-500 via-red-500 to-pink-500 rounded-3xl shadow-2xl hover:shadow-3xl transition-all duration-500 hover:scale-105">
                            <div className="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -mr-20 -mt-20"></div>
                            <div className="relative p-6 text-white">
                                <div className="flex items-start justify-between mb-3">
                                    <div className="bg-white/20 backdrop-blur-sm p-3 rounded-2xl">
                                        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-xs font-medium text-orange-100 mb-1">Total Paid Amount</div>
                                        <div className="text-4xl font-black tracking-tight">
                                            Rp {totalPaidDisplay.value}{totalPaidDisplay.unit}
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-3 pt-3 border-t border-white/20">
                                    <div className="text-sm">{formatRupiah(totalPaidAmount)}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Charts Row */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        {/* Monthly Overview (Begins vs Ends) */}
                        <div className="bg-white rounded-2xl p-6 shadow-lg">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-bold text-gray-800">Monthly Overview (Begins vs Ends)</h3>
                                <div className="flex gap-2">
                                    <button
                                        onClick={() => setChartViewMode('monthly')}
                                        className={`px-4 py-2 rounded-lg text-sm font-medium transition ${
                                            chartViewMode === 'monthly'
                                                ? 'bg-purple-600 text-white shadow-md'
                                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                        }`}
                                    >
                                        Monthly
                                    </button>
                                    <button
                                        onClick={() => setChartViewMode('status')}
                                        className={`px-4 py-2 rounded-lg text-sm font-medium transition ${
                                            chartViewMode === 'status'
                                                ? 'bg-purple-600 text-white shadow-md'
                                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                        }`}
                                    >
                                        Status
                                    </button>
                                </div>
                            </div>
                            
                            {chartViewMode === 'monthly' ? (
                                monthlyOverviewData && monthlyOverviewData.length > 0 ? (
                                    <ResponsiveContainer width="100%" height={300}>
                                        <BarChart data={monthlyOverviewData}>
                                            <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                            <XAxis 
                                                dataKey="month" 
                                                tick={{ fill: '#6b7280', fontSize: 11 }}
                                                angle={-15}
                                                textAnchor="end"
                                                height={50}
                                            />
                                            <YAxis tick={{ fill: '#6b7280', fontSize: 11 }} />
                                            <Tooltip 
                                                contentStyle={{ borderRadius: '8px', border: '1px solid #e5e7eb', padding: '12px' }}
                                                content={({ active, payload, label }) => {
                                                    if (active && payload && payload.length) {
                                                        const data = payload[0].payload;
                                                        return (
                                                            <div className="bg-white rounded-lg border border-gray-200 shadow-lg p-3 min-w-[200px]">
                                                                <p className="font-semibold text-gray-800 mb-2 border-b pb-2">{label}</p>
                                                                <div className="space-y-2">
                                                                    <div>
                                                                        <div className="flex items-center gap-2">
                                                                            <span className="w-3 h-3 rounded-full bg-blue-500"></span>
                                                                            <span className="text-gray-700">Trip Begins: <strong>{data.begins}</strong></span>
                                                                        </div>
                                                                        {data.beginsDates && (
                                                                            <p className="text-xs text-gray-500 ml-5 mt-1">Tanggal: {data.beginsDates}</p>
                                                                        )}
                                                                    </div>
                                                                    <div>
                                                                        <div className="flex items-center gap-2">
                                                                            <span className="w-3 h-3 rounded-full bg-purple-500"></span>
                                                                            <span className="text-gray-700">Trip Ends: <strong>{data.ends}</strong></span>
                                                                        </div>
                                                                        {data.endsDates && (
                                                                            <p className="text-xs text-gray-500 ml-5 mt-1">Tanggal: {data.endsDates}</p>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        );
                                                    }
                                                    return null;
                                                }}
                                            />
                                            <Legend />
                                            <Bar dataKey="begins" fill="#3b82f6" name="Trip Begins" radius={[4, 4, 0, 0]} />
                                            <Bar dataKey="ends" fill="#8b5cf6" name="Trip Ends" radius={[4, 4, 0, 0]} />
                                        </BarChart>
                                    </ResponsiveContainer>
                                ) : (
                                    <div className="h-[300px] flex items-center justify-center text-gray-500">
                                        <div className="text-center">
                                            <svg className="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                            </svg>
                                            <p className="font-semibold">No monthly data available</p>
                                            <p className="text-sm">Data will appear when trips are added</p>
                                        </div>
                                    </div>
                                )
                            ) : (
                                /* Status View - Pie Chart */
                                statusDistributionData.length > 0 ? (
                                    <div className="flex items-center justify-center">
                                        <ResponsiveContainer width="100%" height={300}>
                                            <PieChart>
                                                <Pie
                                                    data={statusDistributionData}
                                                    cx="50%"
                                                    cy="50%"
                                                    innerRadius={60}
                                                    outerRadius={100}
                                                    paddingAngle={3}
                                                    dataKey="value"
                                                    label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                                >
                                                    {statusDistributionData.map((entry, index) => (
                                                        <Cell key={`cell-${index}`} fill={entry.color} />
                                                    ))}
                                                </Pie>
                                                <Tooltip />
                                                <Legend />
                                            </PieChart>
                                        </ResponsiveContainer>
                                    </div>
                                ) : (
                                    <div className="h-[300px] flex items-center justify-center text-gray-500">
                                        <p>No data available</p>
                                    </div>
                                )
                            )}
                            
                            {/* Legend - Only show for Status view */}
                            {chartViewMode === 'status' && (
                                <div className="flex gap-4 justify-center mt-4">
                                    <div className="flex items-center gap-2">
                                        <div className="w-3 h-3 bg-gray-400 rounded-full"></div>
                                        <span className="text-xs text-gray-600">Completed</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                                        <span className="text-xs text-gray-600">Ongoing</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <div className="w-3 h-3 bg-blue-500 rounded-full"></div>
                                        <span className="text-xs text-gray-600">Upcoming</span>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Trips by Reason */}
                        <div className="bg-white rounded-2xl p-6 shadow-lg">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-bold text-gray-800">Trips by Reason</h3>
                                <div className="flex gap-1">
                                    {['all', 'upcoming', 'ongoing', 'completed'].map((status) => (
                                        <button
                                            key={status}
                                            onClick={() => setReasonsStatusFilter(status)}
                                            className={`px-3 py-1.5 rounded-lg text-xs font-medium transition ${
                                                reasonsStatusFilter === status
                                                    ? status === 'upcoming' ? 'bg-blue-500 text-white' :
                                                      status === 'ongoing' ? 'bg-green-500 text-white' :
                                                      status === 'completed' ? 'bg-gray-500 text-white' :
                                                      'bg-purple-500 text-white'
                                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                            }`}
                                        >
                                            {status.charAt(0).toUpperCase() + status.slice(1)}
                                        </button>
                                    ))}
                                </div>
                            </div>
                            
                            <div className="space-y-2 max-h-[300px] overflow-y-auto">
                                {filteredTripsByReason && filteredTripsByReason.length > 0 ? (
                                    filteredTripsByReason.slice(0, 8).map((item, index) => (
                                        <div key={index} className="p-3 bg-gray-50 rounded-lg hover:bg-purple-50 transition">
                                            <div className="flex items-center justify-between">
                                                <div className="flex-1 min-w-0">
                                                    <p className="font-medium text-gray-900 text-sm truncate" title={item.reason}>{item.reason}</p>
                                                    <p className="text-xs text-gray-500">{item.count} trips</p>
                                                </div>
                                                <span className="text-sm font-bold text-purple-600">{item.amount}</span>
                                            </div>
                                            <button
                                                onClick={() => {
                                                    const params = new URLSearchParams();
                                                    params.append('reason', item.reason);
                                                    params.append('sheet', selectedFilter);
                                                    if (selectedStatus !== 'all') params.append('status', selectedStatus);
                                                    if (selectedReason !== 'all') params.append('filter_reason', selectedReason);
                                                    if (selectedBank !== 'all') params.append('bank', selectedBank);
                                                    router.visit(`/sppd/destination-detail?${params.toString()}`);
                                                }}
                                                className="w-full mt-2 px-3 py-1.5 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg hover:from-purple-600 hover:to-pink-600 transition font-medium text-xs"
                                            >
                                                Lihat Detail →
                                            </button>
                                        </div>
                                    ))
                                ) : (
                                    <div className="h-[300px] flex items-center justify-center text-gray-500">
                                        <div className="text-center">
                                            <svg className="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            </svg>
                                            <p className="font-semibold">No trips available</p>
                                            <p className="text-sm">Add a new trip to get started</p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Payment Schedule - Compact */}
                    {paymentChartData && paymentChartData.length > 0 && (
                        <div className="bg-white rounded-2xl p-4 shadow-lg mb-8">
                            <div className="flex items-center gap-3 mb-3">
                                <div className="bg-gradient-to-r from-green-400 to-emerald-500 p-2 rounded-lg">
                                    <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h3 className="text-sm font-bold text-gray-800">Rencana Tanggal Bayar</h3>
                            </div>
                            
                            <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                                {paymentChartData.map((item, index) => (
                                    <div key={index} className="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-2 border border-green-100">
                                        <p className="text-xs text-green-700 font-medium truncate">{item.fullName}</p>
                                        <div className="flex items-baseline gap-1">
                                            <span className="text-lg font-bold text-green-900">{item.transactionCount}</span>
                                            <span className="text-xs text-green-600">trips</span>
                                        </div>
                                        <p className="text-[10px] text-green-500 truncate" title={`Tgl: ${item.dates || '-'}`}>Tgl: {item.dates || '-'}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Bottom Row - 3 Columns */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Trip Status Distribution */}
                        <div className="bg-white rounded-2xl p-6 shadow-lg">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-bold text-gray-800">Trip Status Distribution</h3>
                            </div>
                            
                            {statusDistributionData.length > 0 ? (
                                <ResponsiveContainer width="100%" height={200}>
                                    <PieChart>
                                        <Pie
                                            data={statusDistributionData}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={40}
                                            outerRadius={70}
                                            paddingAngle={3}
                                            dataKey="value"
                                        >
                                            {statusDistributionData.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={entry.color} />
                                            ))}
                                        </Pie>
                                        <Tooltip />
                                    </PieChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="h-[200px] flex items-center justify-center text-gray-500">
                                    <p className="text-sm">No data available</p>
                                </div>
                            )}
                            
                            <div className="flex gap-4 justify-center mt-2">
                                <div className="flex items-center gap-2">
                                    <div className="w-3 h-3 bg-gray-400 rounded-full"></div>
                                    <span className="text-xs text-gray-600">Completed</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                                    <span className="text-xs text-gray-600">Ongoing</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="w-3 h-3 bg-blue-500 rounded-full"></div>
                                    <span className="text-xs text-gray-600">Upcoming</span>
                                </div>
                            </div>
                        </div>

                        {/* Top Customers (with Tab) */}
                        <div className="bg-white rounded-2xl p-6 shadow-lg">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-bold text-gray-800">Top Customers</h3>
                                <div className="flex gap-1">
                                    <button
                                        onClick={() => setCustomerViewMode('trips')}
                                        className={`px-3 py-1 text-sm rounded-lg transition ${customerViewMode === 'trips' ? 'bg-purple-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}
                                    >
                                        Trips
                                    </button>
                                    <button
                                        onClick={() => setCustomerViewMode('amount')}
                                        className={`px-3 py-1 text-sm rounded-lg transition ${customerViewMode === 'amount' ? 'bg-purple-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}
                                    >
                                        Amount
                                    </button>
                                </div>
                            </div>
                            <div className="space-y-2 max-h-[250px] overflow-y-auto">
                                {customerViewMode === 'trips' ? (
                                    // By Trips Count
                                    topCustomersByCount && topCustomersByCount.length > 0 ? (
                                        topCustomersByCount.slice(0, 5).map((customer, index) => (
                                            <div key={index} className="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                                                <div className="flex items-center gap-3 flex-1 min-w-0">
                                                    <div className={`flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold ${
                                                        index === 0 ? 'bg-yellow-400 text-yellow-900' :
                                                        index === 1 ? 'bg-gray-300 text-gray-700' :
                                                        index === 2 ? 'bg-orange-400 text-orange-900' :
                                                        'bg-gray-200 text-gray-600'
                                                    }`}>
                                                        {index + 1}
                                                    </div>
                                                    <p className="text-sm font-medium text-gray-900 truncate">{customer.name}</p>
                                                </div>
                                                <span className="text-sm font-bold text-purple-600">{customer.count} trips</span>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-center text-gray-500 py-4 text-sm">No customer data</p>
                                    )
                                ) : (
                                    // By Amount
                                    topCustomersByAmount && topCustomersByAmount.length > 0 ? (
                                        topCustomersByAmount.slice(0, 5).map((customer, index) => (
                                            <div key={index} className="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                                                <div className="flex items-center gap-3 flex-1 min-w-0">
                                                    <div className={`flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold ${
                                                        index === 0 ? 'bg-yellow-400 text-yellow-900' :
                                                        index === 1 ? 'bg-gray-300 text-gray-700' :
                                                        index === 2 ? 'bg-orange-400 text-orange-900' :
                                                        'bg-gray-200 text-gray-600'
                                                    }`}>
                                                        {index + 1}
                                                    </div>
                                                    <p className="text-sm font-medium text-gray-900 truncate">{customer.name}</p>
                                                </div>
                                                <span className="text-sm font-bold text-green-600">{customer.total}</span>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-center text-gray-500 py-4 text-sm">No customer data</p>
                                    )
                                )}
                            </div>
                        </div>

                        {/* Popular Destinations (Amount) */}
                        <div className="bg-white rounded-2xl p-6 shadow-lg">
                            <h3 className="text-lg font-bold text-gray-800 mb-4">Popular Destinations (Amount)</h3>
                            <div className="space-y-2 max-h-[250px] overflow-y-auto">
                                {popularDestinations && popularDestinations.length > 0 ? (
                                    popularDestinations.slice(0, 5).map((dest, index) => (
                                        <div key={index} className="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                                            <div className="flex items-center gap-3 flex-1 min-w-0">
                                                <div className={`flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold ${
                                                    index === 0 ? 'bg-yellow-400 text-yellow-900' :
                                                    index === 1 ? 'bg-gray-300 text-gray-700' :
                                                    index === 2 ? 'bg-orange-400 text-orange-900' :
                                                    'bg-gray-200 text-gray-600'
                                                }`}>
                                                    {index + 1}
                                                </div>
                                                <p className="text-sm font-medium text-gray-900 truncate" title={dest.destination}>{dest.destination}</p>
                                            </div>
                                            <span className="text-sm font-bold text-green-600">{dest.total}</span>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-center text-gray-500 py-4 text-sm">No destination data</p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}
