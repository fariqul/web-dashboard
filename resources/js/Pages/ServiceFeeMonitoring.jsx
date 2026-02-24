import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';
import CreateServiceFeeModal from '../Components/CreateServiceFeeModalV2';
import ViewServiceFeeModal from '../Components/ViewServiceFeeModal';
import EditServiceFeeModal from '../Components/EditServiceFeeModal';
import DeleteConfirmModal from '../Components/DeleteConfirmModal';
import DeleteSheetModal from '../Components/DeleteSheetModal';
import DeleteAllServiceFeeModal from '../Components/DeleteAllServiceFeeModal';
import SheetSelector from '../Components/SheetSelector';
import Pagination from '../Components/Pagination';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, ResponsiveContainer, Tooltip, Legend, PieChart, Pie, Cell } from 'recharts';
import toast, { Toaster } from 'react-hot-toast';

// Format number to Indonesian format
const formatRupiah = (value) => {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
};

// Custom Tooltip Component for Service Fee
const CustomTooltip = ({ active, payload, label }) => {
    if (active && payload && payload.length) {
        return (
            <div style={{
                backgroundColor: '#fff',
                border: '1px solid #e5e7eb',
                borderRadius: '8px',
                padding: '8px 12px',
                fontSize: '12px',
                boxShadow: '0 2px 8px rgba(0,0,0,0.1)'
            }}>
                <p style={{ fontWeight: 'bold', marginBottom: '4px' }}>{label}</p>
                {payload.map((entry, index) => (
                    <p key={index} style={{ color: entry.color, margin: '2px 0' }}>
                        {entry.name}: {formatRupiah(entry.value)}
                    </p>
                ))}
            </div>
        );
    }
    return null;
};

export default function ServiceFeeMonitoring({
    hotelBookings,
    flightBookings,
    hotelSummary,
    flightSummary,
    availableSheets,
    availableYears,
    selectedSheet,
    selectedYear,
    monthlyChartData,
    monthlySeparatedData,
    serviceTypeBreakdown,
    topDestinations,
    filters,
    flash
}) {
    const [activeTab, setActiveTab] = useState('overview');
    const [searchQuery, setSearchQuery] = useState('');
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [viewModalOpen, setViewModalOpen] = useState(false);
    const [editModalOpen, setEditModalOpen] = useState(false);
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [deleteSheetModalOpen, setDeleteSheetModalOpen] = useState(false);
    const [deleteAllModalOpen, setDeleteAllModalOpen] = useState(false);
    const [isFixingRooms, setIsFixingRooms] = useState(false);
    const [selectedRecord, setSelectedRecord] = useState(null);
    const [sortBy, setSortBy] = useState(filters?.sort_by || 'transaction_time');
    const [sortOrder, setSortOrder] = useState(filters?.sort_order || 'desc');
    const [perPage, setPerPage] = useState(filters?.per_page || 10);
    const [chartView, setChartView] = useState('combined'); // 'combined' or 'separated'

    // Debug log for topDestinations
    useEffect(() => {
        console.log('📍 Top Destinations Debug:', {
            total: topDestinations?.length || 0,
            hotels: topDestinations?.filter(d => d.type === 'hotel').length || 0,
            flights: topDestinations?.filter(d => d.type === 'flight').length || 0,
            data: topDestinations
        });
    }, [topDestinations]);

    // Show flash messages
    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
        if (flash?.info) {
            toast(flash.info, { icon: 'ℹ️' });
        }
    }, [flash]);

    // Handle Fix Empty Room Types
    const handleFixRooms = async () => {
        setIsFixingRooms(true);

        try {
            const response = await fetch('/service-fee/fix-rooms', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();

            if (data.success) {
                toast.success(`Fixed ${data.fixed_count} rooms! ${data.remaining_count} still empty.`, {
                    duration: 5000,
                });

                // Show some examples of unfixed if any
                if (data.remaining_count > 0 && data.unfixed_samples?.length > 0) {
                    toast(`Sample unfixed: ${data.unfixed_samples.slice(0, 3).join(', ')}`, {
                        icon: 'ℹ️',
                        duration: 6000,
                    });
                }

                // Reload page to show updated data
                router.reload();
            } else {
                toast.error(data.message || 'Failed to fix rooms');
            }
        } catch (error) {
            console.error('Fix rooms error:', error);
            toast.error('Failed to fix rooms: ' + error.message);
        } finally {
            setIsFixingRooms(false);
        }
    };

    const handleSheetChange = (sheet) => {
        router.get('/service-fee', {
            sheet,
            year: selectedYear,
            search: searchQuery,
            sort_by: sortBy,
            sort_order: sortOrder,
            per_page: perPage,
        }, { preserveState: true });
    };

    const handleYearChange = (year) => {
        router.get('/service-fee', {
            sheet: selectedSheet,
            year,
            search: searchQuery,
            sort_by: sortBy,
            sort_order: sortOrder,
            per_page: perPage,
        }, { preserveState: true });
    };

    const handleSearch = (e) => {
        const value = e.target.value;
        setSearchQuery(value);

        // Debounce search
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(() => {
            router.get('/service-fee', {
                sheet: selectedSheet,
                search: value,
                sort_by: sortBy,
                sort_order: sortOrder,
                per_page: perPage,
            }, { preserveState: true });
        }, 500);
    };

    const handleSort = (field) => {
        const newSortOrder = sortBy === field && sortOrder === 'asc' ? 'desc' : 'asc';
        setSortBy(field);
        setSortOrder(newSortOrder);

        router.get('/service-fee', {
            sheet: selectedSheet,
            search: searchQuery,
            sort_by: field,
            sort_order: newSortOrder,
            per_page: perPage,
        }, { preserveState: true });
    };

    const handlePerPageChange = (value) => {
        setPerPage(value);
        router.get('/service-fee', {
            sheet: selectedSheet,
            search: searchQuery,
            sort_by: sortBy,
            sort_order: sortOrder,
            per_page: value,
        }, { preserveState: true });
    };

    const handlePageChange = (page) => {
        router.get('/service-fee', {
            sheet: selectedSheet,
            search: searchQuery,
            sort_by: sortBy,
            sort_order: sortOrder,
            per_page: perPage,
            page: page,
        }, { preserveState: true, preserveScroll: true });
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    };

    const COLORS = ['#4AADE8', '#34D399'];  // PLN Blue, Soft Green

    const pieChartData = [
        { name: 'Hotel', value: serviceTypeBreakdown?.hotel || 0 },
        { name: 'Flight', value: serviceTypeBreakdown?.flight || 0 }
    ];

    const getSortIcon = (field) => {
        if (sortBy !== field) return '⇅';
        return sortOrder === 'asc' ? '↑' : '↓';
    };

    return (
        <MainLayout>
            <Toaster
                position="top-right"
                toastOptions={{
                    duration: 3000,
                    style: {
                        background: '#333',
                        color: '#fff',
                    },
                    success: {
                        duration: 3000,
                        iconTheme: {
                            primary: '#10b981',
                            secondary: '#fff',
                        },
                    },
                    error: {
                        duration: 4000,
                        iconTheme: {
                            primary: '#ef4444',
                            secondary: '#fff',
                        },
                    },
                }}
            />

            {/* Hero Header with Gradient */}
            <div className="bg-gradient-to-r from-[#4AADE8] via-[#5BC0EB] to-[#3B9DD6] text-white p-8 shadow-lg mb-8">
                <div className="max-w-7xl mx-auto">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="bg-white/20 backdrop-blur-sm p-4 rounded-2xl">
                                <svg className="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                            </div>
                            <div>
                                <h1 className="text-4xl font-extrabold tracking-tight">Service Fee Monitoring</h1>
                                <p className="text-blue-100 mt-1 text-sm font-medium">Hotel & Flight Service Fee Management</p>
                            </div>
                        </div>

                        <div className="flex gap-3">
                            <button
                                onClick={() => setDeleteAllModalOpen(true)}
                                className="px-5 py-3 bg-red-700/90 backdrop-blur-md hover:bg-red-800 rounded-xl font-semibold transition-all duration-300 flex items-center gap-2 shadow-lg hover:shadow-xl hover:scale-105"
                                title="Delete All Data"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                <span>Delete All</span>
                            </button>
                            <button
                                onClick={() => setDeleteSheetModalOpen(true)}
                                className="px-5 py-3 bg-red-500/80 backdrop-blur-md hover:bg-red-600 rounded-xl font-semibold transition-all duration-300 flex items-center gap-2 shadow-lg hover:shadow-xl hover:scale-105"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                <span>Delete Sheet</span>
                            </button>
                            <button
                                onClick={() => setIsModalOpen(true)}
                                className="px-5 py-3 bg-white/20 backdrop-blur-md hover:bg-white/30 rounded-xl font-semibold transition-all duration-300 flex items-center gap-2 shadow-lg hover:shadow-xl hover:scale-105"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" /></svg>
                                <span>Add Data</span>
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
                                        placeholder="Search by booking ID, hotel, route, or employee..."
                                        value={searchQuery}
                                        onChange={handleSearch}
                                        className="w-full pl-12 pr-4 py-3 bg-cyan-50 border-2 border-cyan-200 rounded-xl focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100 transition-all"
                                    />
                                </div>
                            </div>

                            {/* Year Filter Dropdown */}
                            <div className="relative">
                                <select
                                    value={selectedYear || 'all'}
                                    onChange={(e) => handleYearChange(e.target.value)}
                                    className="appearance-none w-full px-6 py-3 pr-12 bg-gradient-to-r from-purple-500 to-pink-500 text-white border-0 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all cursor-pointer focus:ring-4 focus:ring-purple-200 focus:outline-none"
                                    style={{ color: 'white' }}
                                >
                                    <option value="all" className="text-gray-900 bg-white">All Years</option>
                                    {availableYears?.map(year => (
                                        <option key={year} value={year} className="text-gray-900 bg-white">
                                            {year}
                                        </option>
                                    ))}
                                </select>
                                {/* Custom dropdown arrow */}
                                <div className="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                    <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>

                            {/* Sheet Selector */}
                            <SheetSelector
                                sheets={availableSheets || []}
                                selectedSheet={selectedSheet}
                                onChange={handleSheetChange}
                                className="min-w-[200px]"
                            />
                        </div>
                    </div>

                    {/* Tab Navigation */}
                    <div className="mb-6 bg-white rounded-xl shadow-lg p-2">
                        <nav className="flex space-x-2 overflow-x-auto">
                            <button
                                onClick={() => setActiveTab('overview')}
                                className={`pb-3 px-6 py-2 rounded-lg font-semibold text-sm whitespace-nowrap transition-all ${activeTab === 'overview'
                                    ? 'bg-gradient-to-r from-[#4AADE8] to-[#3B9DD6] text-white shadow-lg'
                                    : 'text-gray-600 hover:bg-gray-100'
                                    }`}
                            >
                                Overview
                            </button>
                            <button
                                onClick={() => setActiveTab('hotel')}
                                className={`pb-3 px-6 py-2 rounded-lg font-semibold text-sm whitespace-nowrap transition-all ${activeTab === 'hotel'
                                    ? 'bg-gradient-to-r from-[#4AADE8] to-[#3B9DD6] text-white shadow-lg'
                                    : 'text-gray-600 hover:bg-gray-100'
                                    }`}
                            >
                                Hotel (HL)
                            </button>
                            <button
                                onClick={() => setActiveTab('flight')}
                                className={`pb-3 px-6 py-2 rounded-lg font-semibold text-sm whitespace-nowrap transition-all ${activeTab === 'flight'
                                    ? 'bg-gradient-to-r from-[#34D399] to-[#22C55E] text-white shadow-lg'
                                    : 'text-gray-600 hover:bg-gray-100'
                                    }`}
                            >
                                Flight (FL)
                            </button>
                        </nav>
                    </div>

                    {/* Overview Tab */}
                    {activeTab === 'overview' && (
                        <div className="space-y-6">
                            {/* Summary Cards */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Hotel Summary */}
                                <div className="bg-gradient-to-br from-sky-50 to-blue-100 rounded-lg p-6 border-l-4 border-[#4AADE8]">
                                    <div className="flex items-center justify-between mb-4">
                                        <h3 className="text-lg font-bold text-gray-900">Hotel Summary</h3>
                                    </div>
                                    <div className="space-y-2">
                                        <div className="flex justify-between">
                                            <span className="text-gray-600">Total Bookings:</span>
                                            <span className="font-semibold">{hotelSummary?.totalBookings || 0}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-gray-600">Transaction Amount:</span>
                                            <span className="font-semibold">{formatCurrency(hotelSummary?.totalTransactionAmount || 0)}</span>
                                        </div>
                                        <hr className="border-sky-200" />
                                        <div className="flex justify-between">
                                            <span className="text-gray-600">Subtotal Service Fee:</span>
                                            <span className="font-semibold">{formatCurrency(hotelSummary?.subtotalServiceFee || 0)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-gray-600">VAT (11%):</span>
                                            <span className="font-semibold">{formatCurrency(hotelSummary?.vat || 0)}</span>
                                        </div>
                                        <hr className="border-sky-300" />
                                        <div className="flex justify-between text-lg">
                                            <span className="font-bold text-gray-900">TOTAL TAGIHAN:</span>
                                            <span className="font-bold text-blue-600">{formatCurrency(hotelSummary?.totalTagihan || 0)}</span>
                                        </div>
                                    </div>
                                </div>

                                {/* Flight Summary */}
                                <div className="bg-gradient-to-br from-emerald-50 to-green-100 rounded-lg p-6 border-l-4 border-[#34D399]">
                                    <div className="flex items-center justify-between mb-4">
                                        <h3 className="text-lg font-bold text-gray-900">Flight Summary</h3>
                                    </div>
                                    <div className="space-y-2">
                                        <div className="flex justify-between">
                                            <span className="text-gray-600">Total Bookings:</span>
                                            <span className="font-semibold">{flightSummary?.totalBookings || 0}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-gray-600">Transaction Amount:</span>
                                            <span className="font-semibold">{formatCurrency(flightSummary?.totalTransactionAmount || 0)}</span>
                                        </div>
                                        <hr className="border-emerald-200" />
                                        <div className="flex justify-between">
                                            <span className="text-gray-600">Subtotal Service Fee:</span>
                                            <span className="font-semibold">{formatCurrency(flightSummary?.subtotalServiceFee || 0)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-gray-600">VAT (11%):</span>
                                            <span className="font-semibold">{formatCurrency(flightSummary?.vat || 0)}</span>
                                        </div>
                                        <hr className="border-emerald-300" />
                                        <div className="flex justify-between text-lg">
                                            <span className="font-bold text-gray-900">TOTAL TAGIHAN:</span>
                                            <span className="font-bold text-green-600">{formatCurrency(flightSummary?.totalTagihan || 0)}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Charts */}
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                {/* Pie Chart - Compact */}
                                <div className="bg-white rounded-lg p-4 shadow">
                                    <div className="flex justify-between items-center mb-3">
                                        <h3 className="text-base font-bold">Service Type Distribution</h3>
                                    </div>
                                    <ResponsiveContainer width="100%" height={350}>
                                        <PieChart>
                                            <Pie
                                                data={pieChartData}
                                                cx="50%"
                                                cy="50%"
                                                labelLine={false}
                                                label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                                outerRadius={90}
                                                fill="#8884d8"
                                                dataKey="value"
                                            >
                                                {pieChartData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                                ))}
                                            </Pie>
                                            <Tooltip content={<CustomTooltip />} />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </div>

                                {/* Bar Chart - Compact Version */}
                                <div className="bg-white rounded-lg p-4 shadow">
                                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-3">
                                        <h3 className="text-base font-bold">Monthly Trend - Total Tagihan (incl. VAT)</h3>

                                        <div className="flex items-center gap-2">
                                            {/* Toggle Switch */}
                                            <div className="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                                                <button
                                                    onClick={() => setChartView('combined')}
                                                    className={`px-3 py-1.5 rounded-md text-xs font-medium transition ${chartView === 'combined'
                                                        ? 'bg-white text-cyan-600 shadow'
                                                        : 'text-gray-600 hover:text-gray-900'
                                                        }`}
                                                >
                                                    Combined
                                                </button>
                                                <button
                                                    onClick={() => setChartView('separated')}
                                                    className={`px-3 py-1.5 rounded-md text-xs font-medium transition ${chartView === 'separated'
                                                        ? 'bg-white text-cyan-600 shadow'
                                                        : 'text-gray-600 hover:text-gray-900'
                                                        }`}
                                                >
                                                    HL & FL
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="overflow-x-auto">
                                        {chartView === 'combined' ? (
                                            <ResponsiveContainer width={Math.max(400, monthlyChartData?.length * 80)} height={350}>
                                                <BarChart data={monthlyChartData} margin={{ left: 10, right: 10, top: 5, bottom: 5 }}>
                                                    <CartesianGrid strokeDasharray="3 3" opacity={0.3} />
                                                    <XAxis
                                                        dataKey="sheet"
                                                        angle={-35}
                                                        textAnchor="end"
                                                        height={70}
                                                        interval={0}
                                                        tick={{ fontSize: 12 }}
                                                    />
                                                    <YAxis
                                                        tickFormatter={(value) => formatRupiah(value)}
                                                        tick={{ fontSize: 11 }}
                                                        width={90}
                                                    />
                                                    <Tooltip content={<CustomTooltip />} />
                                                    <Legend wrapperStyle={{ fontSize: '13px' }} />
                                                    <Bar
                                                        dataKey="fee"
                                                        fill="#4AADE8"
                                                        name="Total Tagihan (+ VAT)"
                                                        radius={[6, 6, 0, 0]}
                                                        maxBarSize={70}
                                                    />
                                                </BarChart>
                                            </ResponsiveContainer>
                                        ) : (
                                            <ResponsiveContainer width={Math.max(400, monthlySeparatedData?.length * 100)} height={350}>
                                                <BarChart data={monthlySeparatedData} margin={{ left: 10, right: 10, top: 5, bottom: 5 }}>
                                                    <CartesianGrid strokeDasharray="3 3" opacity={0.3} />
                                                    <XAxis
                                                        dataKey="sheet"
                                                        angle={-35}
                                                        textAnchor="end"
                                                        height={70}
                                                        interval={0}
                                                        tick={{ fontSize: 12 }}
                                                    />
                                                    <YAxis
                                                        tickFormatter={(value) => formatRupiah(value)}
                                                        tick={{ fontSize: 11 }}
                                                        width={90}
                                                    />
                                                    <Tooltip content={<CustomTooltip />} />
                                                    <Legend wrapperStyle={{ fontSize: '13px' }} />
                                                    <Bar
                                                        dataKey="hotel"
                                                        fill="#4AADE8"
                                                        name="Hotel (HL)"
                                                        radius={[6, 6, 0, 0]}
                                                        maxBarSize={50}
                                                    />
                                                    <Bar
                                                        dataKey="flight"
                                                        fill="#34D399"
                                                        name="Flight (FL)"
                                                        radius={[6, 6, 0, 0]}
                                                        maxBarSize={50}
                                                    />
                                                </BarChart>
                                            </ResponsiveContainer>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Top Destinations - Combined HL & FL */}
                            <div className="bg-white rounded-lg p-6 shadow">
                                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                                    <h3 className="text-lg font-bold">Top Destinations by Service Fee</h3>
                                    <div className="flex items-center gap-3 text-sm">
                                        <span className="text-gray-500">
                                            {topDestinations?.length || 0} total
                                        </span>
                                        <div className="flex items-center gap-2">
                                            <span className="flex items-center gap-1">
                                                <span>HL</span>
                                                <span className="text-xs text-gray-600">
                                                    {topDestinations?.filter(d => d.type === 'hotel').length || 0} Hotels
                                                </span>
                                            </span>
                                            <span className="text-gray-300">•</span>
                                            <span className="flex items-center gap-1">
                                                <span>FL</span>
                                                <span className="text-xs text-gray-600">
                                                    {topDestinations?.filter(d => d.type === 'flight').length || 0} Flights
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {topDestinations && topDestinations.length > 0 ? (
                                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        {/* Top Hotels */}
                                        <div>
                                            <div className="flex items-center gap-2 mb-3 pb-2 border-b border-sky-200">
                                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={1.8}><path d="M6 22V4a2 2 0 012-2h8a2 2 0 012 2v18" /><path d="M2 22h20" /><path d="M10 6h4" /><path d="M10 10h4" /><path d="M10 14h4" /></svg>
                                                <h4 className="font-semibold text-sky-800">Top Hotels</h4>
                                            </div>
                                            <div className="space-y-2">
                                                {topDestinations?.filter(d => d.type === 'hotel').slice(0, 3).map((dest, index) => (
                                                    <div key={index} className="flex items-center gap-3 p-2 bg-sky-50 rounded-lg hover:bg-sky-100 transition">
                                                        {/* Rank Badge */}
                                                        <div className={`flex-shrink-0 w-7 h-7 flex items-center justify-center rounded-full font-bold text-xs ${index === 0 ? 'bg-[#F5C842] text-amber-900' :
                                                            index === 1 ? 'bg-gray-300 text-gray-800' :
                                                                index === 2 ? 'bg-orange-300 text-orange-900' :
                                                                    'bg-sky-200 text-sky-700'
                                                            }`}>
                                                            {index + 1}
                                                        </div>

                                                        {/* Name */}
                                                        <div className="flex-1 min-w-0">
                                                            <p className="font-medium text-gray-900 text-sm truncate">
                                                                {dest.name}
                                                            </p>
                                                            <p className="text-xs text-gray-600">{dest.bookings}</p>
                                                        </div>

                                                        {/* Amount */}
                                                        <div className="flex-shrink-0 text-right">
                                                            <p className="text-sm font-bold text-[#4AADE8]">{dest.amount}</p>
                                                        </div>
                                                    </div>
                                                ))}
                                                {topDestinations?.filter(d => d.type === 'hotel').length === 0 && (
                                                    <p className="text-center text-sm text-gray-400 py-4">No hotel data</p>
                                                )}
                                            </div>
                                        </div>

                                        {/* Top Flights */}
                                        <div>
                                            <div className="flex items-center gap-2 mb-3 pb-2 border-b border-emerald-200">
                                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={1.8}><path d="M17.8 19.2L16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z" /></svg>
                                                <h4 className="font-semibold text-emerald-800">Top Flights</h4>
                                            </div>
                                            <div className="space-y-2">
                                                {topDestinations?.filter(d => d.type === 'flight').slice(0, 3).map((dest, index) => (
                                                    <div key={index} className="flex items-center gap-3 p-2 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition">
                                                        {/* Rank Badge */}
                                                        <div className={`flex-shrink-0 w-7 h-7 flex items-center justify-center rounded-full font-bold text-xs ${index === 0 ? 'bg-[#F5C842] text-amber-900' :
                                                            index === 1 ? 'bg-gray-300 text-gray-800' :
                                                                index === 2 ? 'bg-orange-300 text-orange-900' :
                                                                    'bg-emerald-200 text-emerald-700'
                                                            }`}>
                                                            {index + 1}
                                                        </div>

                                                        {/* Name */}
                                                        <div className="flex-1 min-w-0">
                                                            <p className="font-medium text-gray-900 text-sm truncate">
                                                                {dest.name}
                                                            </p>
                                                            <p className="text-xs text-gray-600">{dest.bookings}</p>
                                                        </div>

                                                        {/* Amount */}
                                                        <div className="flex-shrink-0 text-right">
                                                            <p className="text-sm font-bold text-[#34D399]">{dest.amount}</p>
                                                        </div>
                                                    </div>
                                                ))}
                                                {topDestinations?.filter(d => d.type === 'flight').length === 0 && (
                                                    <p className="text-center text-sm text-gray-400 py-4">No flight data</p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="text-center py-8 text-gray-500">
                                        <p className="text-4xl mb-2">📍</p>
                                        <p>No destination data available</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Hotel Tab */}
                    {activeTab === 'hotel' && (
                        <HotelTab
                            bookings={hotelBookings}
                            summary={hotelSummary}
                            formatCurrency={formatCurrency}
                            onView={(booking) => { setSelectedRecord(booking); setViewModalOpen(true); }}
                            onEdit={(booking) => { setSelectedRecord(booking); setEditModalOpen(true); }}
                            onDelete={(booking) => { setSelectedRecord(booking); setDeleteModalOpen(true); }}
                            onSort={handleSort}
                            getSortIcon={getSortIcon}
                            sortBy={sortBy}
                            onPageChange={handlePageChange}
                            onPerPageChange={handlePerPageChange}
                            perPage={perPage}
                            onFixRooms={handleFixRooms}
                            isFixingRooms={isFixingRooms}
                        />
                    )}

                    {/* Flight Tab */}
                    {activeTab === 'flight' && (
                        <FlightTab
                            bookings={flightBookings}
                            summary={flightSummary}
                            formatCurrency={formatCurrency}
                            onView={(booking) => { setSelectedRecord(booking); setViewModalOpen(true); }}
                            onEdit={(booking) => { setSelectedRecord(booking); setEditModalOpen(true); }}
                            onDelete={(booking) => { setSelectedRecord(booking); setDeleteModalOpen(true); }}
                            onSort={handleSort}
                            getSortIcon={getSortIcon}
                            sortBy={sortBy}
                            onPageChange={handlePageChange}
                            onPerPageChange={handlePerPageChange}
                            perPage={perPage}
                        />
                    )}
                </div>
            </div>

            {/* Modals */}
            <CreateServiceFeeModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                availableSheets={availableSheets}
            />
            <ViewServiceFeeModal
                isOpen={viewModalOpen}
                onClose={() => setViewModalOpen(false)}
                data={selectedRecord}
            />
            <EditServiceFeeModal
                isOpen={editModalOpen}
                onClose={() => setEditModalOpen(false)}
                data={selectedRecord}
                availableSheets={availableSheets}
            />
            <DeleteConfirmModal
                isOpen={deleteModalOpen}
                onClose={() => setDeleteModalOpen(false)}
                data={selectedRecord}
            />
            <DeleteSheetModal
                isOpen={deleteSheetModalOpen}
                onClose={() => setDeleteSheetModalOpen(false)}
                sheets={availableSheets}
            />
            <DeleteAllServiceFeeModal
                isOpen={deleteAllModalOpen}
                onClose={() => setDeleteAllModalOpen(false)}
                totalRecords={(hotelSummary?.totalBookings || 0) + (flightSummary?.totalBookings || 0)}
                hotelCount={hotelSummary?.totalBookings || 0}
                flightCount={flightSummary?.totalBookings || 0}
            />
        </MainLayout>
    );
}

function HotelTab({ bookings, summary, formatCurrency, onView, onEdit, onDelete, onSort, getSortIcon, sortBy, onPageChange, onPerPageChange, perPage, onFixRooms, isFixingRooms }) {
    return (
        <div className="space-y-6">
            {/* Summary Card */}
            <div className="bg-gradient-to-br from-sky-50 to-blue-100 rounded-lg p-6 border-l-4 border-[#4AADE8]">
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-xl font-bold text-gray-900">Hotel Summary</h3>
                    <button
                        onClick={onFixRooms}
                        disabled={isFixingRooms}
                        className="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-white text-sm rounded-lg font-medium transition-all duration-200 flex items-center gap-1.5 shadow hover:shadow-md"
                        title="Fix Empty Room Types"
                    >
                        <span>{isFixingRooms ? '⏳' : '🔧'}</span>
                        <span>{isFixingRooms ? 'Fixing...' : 'Fix Rooms'}</span>
                    </button>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p className="text-sm text-gray-600">Total Bookings</p>
                        <p className="text-2xl font-bold">{summary?.totalBookings || 0}</p>
                    </div>
                    <div>
                        <p className="text-sm text-gray-600">Subtotal Service Fee</p>
                        <p className="text-2xl font-bold">{formatCurrency(summary?.subtotalServiceFee || 0)}</p>
                    </div>
                    <div>
                        <p className="text-sm text-gray-600">Total Tagihan (+ VAT)</p>
                        <p className="text-2xl font-bold text-blue-600">{formatCurrency(summary?.totalTagihan || 0)}</p>
                    </div>
                </div>
            </div>

            {/* Per Page Selector */}
            <div className="flex justify-between items-center">
                <div className="flex items-center gap-2">
                    <label className="text-sm text-gray-600">Show:</label>
                    <select
                        value={perPage}
                        onChange={(e) => onPerPageChange(Number(e.target.value))}
                        className="px-3 py-1 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-cyan-300"
                    >
                        <option value={10}>10</option>
                        <option value={25}>25</option>
                        <option value={50}>50</option>
                        <option value={100}>100</option>
                    </select>
                    <span className="text-sm text-gray-600">entries</span>
                </div>
            </div>

            {/* Table */}
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-blue-50">
                            <tr>
                                <th
                                    onClick={() => onSort('booking_id')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-blue-100 transition"
                                >
                                    Booking ID {getSortIcon('booking_id')}
                                </th>
                                <th
                                    onClick={() => onSort('transaction_time')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-blue-100 transition"
                                >
                                    Date {getSortIcon('transaction_time')}
                                </th>
                                <th
                                    onClick={() => onSort('hotel_name')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-blue-100 transition"
                                >
                                    Hotel {getSortIcon('hotel_name')}
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Room Type</th>
                                <th
                                    onClick={() => onSort('employee_name')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-blue-100 transition"
                                >
                                    Employee {getSortIcon('employee_name')}
                                </th>
                                <th
                                    onClick={() => onSort('transaction_amount')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-blue-100 transition"
                                >
                                    Amount {getSortIcon('transaction_amount')}
                                </th>
                                <th
                                    onClick={() => onSort('service_fee')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-blue-100 transition"
                                >
                                    Service Fee {getSortIcon('service_fee')}
                                </th>
                                <th
                                    onClick={() => onSort('status')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-blue-100 transition"
                                >
                                    Status {getSortIcon('status')}
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {bookings?.data?.map((booking) => (
                                <tr key={booking.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{booking.booking_id}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{booking.transaction_time}</td>
                                    <td className="px-6 py-4 text-sm text-gray-900">{booking.hotel_name}</td>
                                    <td className="px-6 py-4 text-sm text-gray-600">{booking.room_type}</td>
                                    <td className="px-6 py-4 text-sm text-gray-600">{booking.employee_name}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{formatCurrency(booking.transaction_amount)}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-semibold text-blue-600">{formatCurrency(booking.service_fee)}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            {booking.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                        <div className="flex gap-2">
                                            <button onClick={() => onView(booking)}
                                                className="text-blue-600 hover:text-blue-800" title="View">
                                                👁️
                                            </button>
                                            <button onClick={() => onEdit(booking)}
                                                className="text-yellow-600 hover:text-yellow-800" title="Edit">
                                                Edit
                                            </button>
                                            <button onClick={() => onDelete(booking)}
                                                className="text-red-600 hover:text-red-800" title="Delete">
                                                Del
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                {(!bookings?.data || bookings?.data?.length === 0) && (
                    <div className="text-center py-12 text-gray-500">
                        No hotel bookings found
                    </div>
                )}

                {/* Pagination */}
                <Pagination data={bookings} onPageChange={onPageChange} />
            </div>
        </div>
    );
}

function FlightTab({ bookings, summary, formatCurrency, onView, onEdit, onDelete, onSort, getSortIcon, sortBy, onPageChange, onPerPageChange, perPage }) {
    return (
        <div className="space-y-6">
            {/* Summary Card */}
            <div className="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-6 border-l-4 border-green-500">
                <h3 className="text-xl font-bold text-gray-900 mb-4">Flight Summary</h3>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p className="text-sm text-gray-600">Total Bookings</p>
                        <p className="text-2xl font-bold">{summary?.totalBookings || 0}</p>
                    </div>
                    <div>
                        <p className="text-sm text-gray-600">Subtotal Service Fee</p>
                        <p className="text-2xl font-bold">{formatCurrency(summary?.subtotalServiceFee || 0)}</p>
                    </div>
                    <div>
                        <p className="text-sm text-gray-600">Total Tagihan (+ VAT)</p>
                        <p className="text-2xl font-bold text-green-600">{formatCurrency(summary?.totalTagihan || 0)}</p>
                    </div>
                </div>
            </div>

            {/* Per Page Selector */}
            <div className="flex justify-between items-center">
                <div className="flex items-center gap-2">
                    <label className="text-sm text-gray-600">Show:</label>
                    <select
                        value={perPage}
                        onChange={(e) => onPerPageChange(Number(e.target.value))}
                        className="px-3 py-1 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-cyan-300"
                    >
                        <option value={10}>10</option>
                        <option value={25}>25</option>
                        <option value={50}>50</option>
                        <option value={100}>100</option>
                    </select>
                    <span className="text-sm text-gray-600">entries</span>
                </div>
            </div>

            {/* Table */}
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-green-50">
                            <tr>
                                <th
                                    onClick={() => onSort('booking_id')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-green-100 transition"
                                >
                                    Booking ID {getSortIcon('booking_id')}
                                </th>
                                <th
                                    onClick={() => onSort('transaction_time')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-green-100 transition"
                                >
                                    Date {getSortIcon('transaction_time')}
                                </th>
                                <th
                                    onClick={() => onSort('route')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-green-100 transition"
                                >
                                    Route {getSortIcon('route')}
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Trip Type</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Pax</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Airline</th>
                                <th
                                    onClick={() => onSort('employee_name')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-green-100 transition"
                                >
                                    Employee {getSortIcon('employee_name')}
                                </th>
                                <th
                                    onClick={() => onSort('transaction_amount')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-green-100 transition"
                                >
                                    Amount {getSortIcon('transaction_amount')}
                                </th>
                                <th
                                    onClick={() => onSort('service_fee')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-green-100 transition"
                                >
                                    Service Fee {getSortIcon('service_fee')}
                                </th>
                                <th
                                    onClick={() => onSort('status')}
                                    className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-green-100 transition"
                                >
                                    Status {getSortIcon('status')}
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {bookings?.data?.map((booking) => (
                                <tr key={booking.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{booking.booking_id}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{booking.transaction_time}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{booking.route}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{booking.trip_type}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{booking.pax}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{booking.airline_id}</td>
                                    <td className="px-6 py-4 text-sm text-gray-600">{booking.employee_name}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{formatCurrency(booking.transaction_amount)}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">{formatCurrency(booking.service_fee)}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            {booking.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                        <div className="flex gap-2">
                                            <button onClick={() => onView(booking)}
                                                className="text-blue-600 hover:text-blue-800" title="View">
                                                👁️
                                            </button>
                                            <button onClick={() => onEdit(booking)}
                                                className="text-yellow-600 hover:text-yellow-800" title="Edit">
                                                Edit
                                            </button>
                                            <button onClick={() => onDelete(booking)}
                                                className="text-red-600 hover:text-red-800" title="Delete">
                                                Del
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                {(!bookings?.data || bookings?.data?.length === 0) && (
                    <div className="text-center py-12 text-gray-500">
                        No flight bookings found
                    </div>
                )}

                {/* Pagination */}
                <Pagination data={bookings} onPageChange={onPageChange} />
            </div>
        </div>
    );
}