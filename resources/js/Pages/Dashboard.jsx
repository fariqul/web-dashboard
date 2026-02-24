import React, { useState } from 'react';
import MainLayout from '../Layouts/MainLayout';
import { Link, router } from '@inertiajs/react';
import { PieChart, Pie, Cell, BarChart, Bar, XAxis, YAxis, CartesianGrid, ResponsiveContainer, Tooltip } from 'recharts';

// ── SVG Icon Components ──────────────────────────────────────────────
const Icons = {
    Bolt: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
        </svg>
    ),
    CreditCard: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <rect x="2" y="5" width="20" height="14" rx="2" />
            <line x1="2" y1="10" x2="22" y2="10" />
        </svg>
    ),
    Building: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 22V4a2 2 0 012-2h8a2 2 0 012 2v18" />
            <path d="M2 22h20" />
            <path d="M10 6h4" /><path d="M10 10h4" /><path d="M10 14h4" />
        </svg>
    ),
    Clipboard: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2" />
            <rect x="8" y="2" width="8" height="4" rx="1" />
            <path d="M9 12h6" /><path d="M9 16h6" />
        </svg>
    ),
    Hotel: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 22V4a2 2 0 012-2h8a2 2 0 012 2v18" />
            <path d="M2 22h20" />
            <path d="M10 6h4" /><path d="M10 10h4" /><path d="M10 14h4" />
        </svg>
    ),
    Plane: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M17.8 19.2L16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z" />
        </svg>
    ),
    ArrowRight: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M5 12h14" /><path d="M12 5l7 7-7 7" />
        </svg>
    ),
    Check: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.5} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <polyline points="20 6 9 17 4 12" />
        </svg>
    ),
    Users: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 00-3-3.87" /><path d="M16 3.13a4 4 0 010 7.75" />
        </svg>
    ),
    Receipt: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1z" />
            <path d="M8 10h8" /><path d="M8 14h4" />
        </svg>
    ),
};

// ── Card Config ──────────────────────────────────────────────────────
const cardConfig = [
    {
        key: 'bfko', href: '/bfko', label: 'BFKO Monitoring', icon: Icons.Bolt,
        gradient: 'from-[#F5C842] via-[#F7D56B] to-[#E5B82E]',
        shadowColor: 'shadow-amber-300/30',
        borderColor: 'border-[#C9A128]',
        getSummary: (s) => ({ total: s?.bfko?.total || 0, count: s?.bfko?.count || 0, label: 'transaksi', extra: `${s?.bfko?.employees || 0} pegawai` }),
    },
    {
        key: 'ccCard', href: '/cc-card', label: 'CC Card Monitoring', icon: Icons.CreditCard,
        gradient: 'from-[#4AADE8] via-[#5BC0EB] to-[#3B9DD6]',
        shadowColor: 'shadow-sky-300/30',
        borderColor: 'border-[#2D87BE]',
        getSummary: (s) => ({ total: s?.ccCard?.total || 0, count: s?.ccCard?.count || 0, label: 'transaksi', extra: `${s?.ccCard?.employees || 0} pegawai` }),
    },
    {
        key: 'serviceFee', href: '/service-fee', label: 'Service Fee', icon: Icons.Building,
        gradient: 'from-[#34D399] via-[#4ADE80] to-[#22C55E]',
        shadowColor: 'shadow-emerald-300/30',
        borderColor: 'border-[#16A34A]',
        getSummary: (s) => ({ total: s?.serviceFee?.total || 0, count: null, label: null, extra: null, hotel: s?.serviceFee?.hotel || 0, flight: s?.serviceFee?.flight || 0 }),
    },
    {
        key: 'sppd', href: '/sppd', label: 'SPPD Monitoring', icon: Icons.Clipboard,
        gradient: 'from-[#E8636B] via-[#F07D84] to-[#D64F57]',
        shadowColor: 'shadow-rose-300/30',
        borderColor: 'border-[#BE3F47]',
        getSummary: (s) => ({ total: s?.sppd?.total || 0, count: s?.sppd?.count || 0, label: 'trips', extra: `${s?.sppd?.employees || 0} pegawai` }),
    },
];

export default function Dashboard({ summary, monthlyData, recentTransactions, fundSource = '54' }) {
    const [selectedFund, setSelectedFund] = useState(fundSource || '54');

    const handleFundChange = (fund) => {
        setSelectedFund(fund);
        router.get('/', { fund }, { preserveState: true, preserveScroll: true });
    };

    const getCategoryData = () => {
        let categories = [];
        if (selectedFund === '52') {
            categories.push({ name: 'BFKO', value: summary?.bfko?.total || 0, color: '#F5C842', fund: '52' });
        }
        if (selectedFund === '54') {
            categories.push(
                { name: 'CC Card', value: summary?.ccCard?.total || 0, color: '#4AADE8', fund: '54' },
                { name: 'Service Fee', value: summary?.serviceFee?.total || 0, color: '#34D399', fund: '54' },
                { name: 'SPPD', value: summary?.sppd?.total || 0, color: '#E8636B', fund: '54' },
            );
        }
        const filtered = categories.filter(item => item.value > 0);
        const totalAmount = filtered.reduce((sum, item) => sum + item.value, 0);
        return filtered.map(item => ({
            ...item,
            percentage: totalAmount > 0 ? ((item.value / totalAmount) * 100).toFixed(1) : 0
        }));
    };

    const categoryData = getCategoryData();

    const formatCurrency = (amount) => {
        if (amount >= 1000000000) return 'Rp' + (amount / 1000000000).toFixed(1) + 'M';
        if (amount >= 1000000) return 'Rp' + (amount / 1000000).toFixed(1) + 'Jt';
        return 'Rp' + amount.toLocaleString('id-ID');
    };

    const categoryLinks = { 'BFKO': '/bfko', 'CC Card': '/cc-card', 'Service Fee': '/service-fee', 'SPPD': '/sppd' };
    const categoryIcons = { 'BFKO': Icons.Bolt, 'CC Card': Icons.CreditCard, 'Service Fee': Icons.Building, 'SPPD': Icons.Clipboard };

    return (
        <MainLayout>
            <div className="p-6 lg:p-8 min-h-screen">
                {/* Header */}
                <div className="mb-8" style={{ animation: 'slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) both' }}>
                    <h1 className="text-3xl font-extrabold text-gray-900 tracking-tight font-display">
                        Dashboard Overview
                    </h1>
                    <p className="text-sm text-gray-500 mt-1 font-sans">
                        Monitoring Dashboard PLN — Real-time Data Analytics
                    </p>
                </div>

                {/* Quick Stats Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
                    {cardConfig.map((card, idx) => {
                        const data = card.getSummary(summary);
                        return (
                            <Link
                                key={card.key}
                                href={card.href}
                                className="group block"
                                style={{ animation: `slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) ${100 + idx * 80}ms both` }}
                            >
                                <div className={`relative overflow-hidden bg-gradient-to-br ${card.gradient} rounded-2xl p-5 
                                    shadow-lg ${card.shadowColor} hover:shadow-xl transition-all duration-300 
                                    group-hover:-translate-y-1 border-l-4 ${card.borderColor}`}>
                                    {/* Decorative circle */}
                                    <div className="absolute -top-6 -right-6 w-24 h-24 bg-white/10 rounded-full 
                                        group-hover:scale-150 transition-transform duration-700" />

                                    <div className="relative">
                                        <div className="flex items-center justify-between mb-3">
                                            <div className="w-11 h-11 bg-white/25 backdrop-blur-sm rounded-xl flex items-center justify-center
                                                group-hover:rotate-6 transition-transform duration-500">
                                                <card.icon className="w-5 h-5 text-white" />
                                            </div>
                                            <span className="text-white/70 text-xs font-semibold tracking-wide uppercase font-display">
                                                {card.label}
                                            </span>
                                        </div>

                                        <p className="text-white text-2xl font-extrabold mb-2 tracking-tight font-display">
                                            {formatCurrency(data.total)}
                                        </p>

                                        <div className="flex items-center justify-between text-white/80 text-xs font-medium">
                                            {data.hotel !== undefined ? (
                                                <>
                                                    <span className="flex items-center gap-1">
                                                        <Icons.Hotel className="w-3 h-3" /> {data.hotel} hotel
                                                    </span>
                                                    <span className="flex items-center gap-1">
                                                        <Icons.Plane className="w-3 h-3" /> {data.flight} pesawat
                                                    </span>
                                                </>
                                            ) : (
                                                <>
                                                    <span>{data.count} {data.label}</span>
                                                    <span>{data.extra}</span>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        );
                    })}
                </div>

                {/* Charts Row */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    {/* Category Distribution */}
                    <div className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100"
                        style={{ animation: 'slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) 500ms both' }}>
                        <div className="flex items-center justify-between mb-2">
                            <div>
                                <h3 className="text-lg font-bold text-gray-900 font-display">Distribution by Category</h3>
                                <p className="text-xs text-gray-400 mt-0.5">Based on total transaction value (Rupiah)</p>
                            </div>
                            <div className="flex gap-1.5">
                                <button
                                    onClick={() => handleFundChange('54')}
                                    className={`px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 cursor-pointer ${selectedFund === '54'
                                            ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200/50'
                                            : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
                                        }`}
                                >
                                    Fund 54
                                </button>
                                <button
                                    onClick={() => handleFundChange('52')}
                                    className={`px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 cursor-pointer ${selectedFund === '52'
                                            ? 'bg-amber-500 text-white shadow-md shadow-amber-200/50'
                                            : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
                                        }`}
                                >
                                    Fund 52
                                </button>
                            </div>
                        </div>
                        <div className="text-[10px] text-gray-400 mb-4 font-medium">
                            {selectedFund === '54' && 'Fund 54: Service Fee, CC Card, SPPD'}
                            {selectedFund === '52' && 'Fund 52: BFKO'}
                        </div>
                        <div className="flex items-center justify-center">
                            <div className="w-48 h-48">
                                {categoryData.length > 0 ? (
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie data={categoryData} cx="50%" cy="50%" innerRadius={60} outerRadius={90} paddingAngle={3} dataKey="value">
                                                {categoryData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={entry.color} />
                                                ))}
                                            </Pie>
                                            <Tooltip formatter={(value) => formatCurrency(value)} />
                                        </PieChart>
                                    </ResponsiveContainer>
                                ) : (
                                    <div className="flex items-center justify-center h-full text-gray-400 text-sm">
                                        <p>No data available</p>
                                    </div>
                                )}
                            </div>
                            <div className="ml-8 space-y-3">
                                {categoryData.map((item, index) => {
                                    const ItemIcon = categoryIcons[item.name];
                                    return (
                                        <Link key={index} href={categoryLinks[item.name]} className="block hover:opacity-70 transition group cursor-pointer">
                                            <div className="flex items-center justify-between mb-0.5">
                                                <div className="flex items-center gap-2">
                                                    <div className="w-3 h-3 rounded-full" style={{ backgroundColor: item.color }} />
                                                    <ItemIcon className="w-3.5 h-3.5 text-gray-500" />
                                                    <span className="text-xs font-semibold text-gray-700 font-display">{item.name}</span>
                                                </div>
                                                {categoryData.length > 1 && (
                                                    <span className="text-xs font-bold text-gray-800 ml-4">{item.percentage}%</span>
                                                )}
                                            </div>
                                            <div className="ml-5 text-[10px] text-gray-400">
                                                {formatCurrency(item.value)} • {
                                                    item.name === 'BFKO' ? summary?.bfko?.count :
                                                        item.name === 'CC Card' ? summary?.ccCard?.count :
                                                            item.name === 'Service Fee' ? summary?.serviceFee?.count :
                                                                summary?.sppd?.count
                                                } records
                                            </div>
                                        </Link>
                                    );
                                })}
                                <div className={`mt-3 pt-3 border-t border-gray-100 ${categoryData.length === 1 ? 'bg-amber-50 -mx-2 px-2 py-2 rounded-lg' : ''}`}>
                                    <div className={categoryData.length === 1 ? 'text-sm text-amber-800' : 'text-[10px] text-gray-500'}>
                                        <span className="font-semibold">Grand Total: </span>
                                        <span className={categoryData.length === 1 ? 'text-lg font-bold' : 'font-bold'}>
                                            {formatCurrency(categoryData.reduce((sum, item) => sum + item.value, 0))}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Monthly Trend */}
                    <div className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100"
                        style={{ animation: 'slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) 600ms both' }}>
                        <h3 className="text-lg font-bold text-gray-900 mb-1 font-display">Monthly Trend Comparison</h3>
                        <p className="text-xs text-gray-400 mb-5">All categories by month</p>
                        <ResponsiveContainer width="100%" height={250}>
                            <BarChart data={monthlyData || []}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                                <XAxis dataKey="month" tick={{ fill: '#9ca3af', fontSize: 11 }} />
                                <YAxis tick={{ fill: '#9ca3af', fontSize: 11 }}
                                    tickFormatter={(value) => {
                                        if (value >= 1000000000) return `${(value / 1000000000).toFixed(1)}M`;
                                        if (value >= 1000000) return `${(value / 1000000).toFixed(0)}Jt`;
                                        return value;
                                    }}
                                />
                                <Tooltip
                                    formatter={(value, name) => {
                                        const labels = { 'bfko': 'BFKO', 'ccCard': 'CC Card', 'serviceFee': 'Service Fee', 'sppd': 'SPPD' };
                                        return [formatCurrency(value), labels[name] || name];
                                    }}
                                    contentStyle={{ borderRadius: '12px', border: '1px solid #f0f0f0', fontSize: '12px' }}
                                />
                                <Bar dataKey="bfko" fill="#F5C842" radius={[4, 4, 0, 0]} name="BFKO" />
                                <Bar dataKey="ccCard" fill="#4AADE8" radius={[4, 4, 0, 0]} name="CC Card" />
                                <Bar dataKey="serviceFee" fill="#34D399" radius={[4, 4, 0, 0]} name="Service Fee" />
                                <Bar dataKey="sppd" fill="#E8636B" radius={[4, 4, 0, 0]} name="SPPD" />
                            </BarChart>
                        </ResponsiveContainer>

                        {/* Legend */}
                        <div className="flex gap-5 justify-center mt-4">
                            {[
                                { key: 'BFKO', color: '#F5C842', icon: Icons.Bolt },
                                { key: 'CC Card', color: '#4AADE8', icon: Icons.CreditCard },
                                { key: 'Service Fee', color: '#34D399', icon: Icons.Building },
                                { key: 'SPPD', color: '#E8636B', icon: Icons.Clipboard },
                            ].map(item => (
                                <div key={item.key} className="flex items-center gap-1.5">
                                    <div className="w-2.5 h-2.5 rounded-sm" style={{ backgroundColor: item.color }} />
                                    <span className="text-[10px] text-gray-500 font-medium">{item.key}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Recent Transactions Table */}
                <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                    style={{ animation: 'slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) 700ms both' }}>
                    <div className="px-6 py-5 border-b border-gray-100">
                        <h2 className="text-lg font-bold text-gray-900 font-display">Recent Transactions</h2>
                        <p className="text-xs text-gray-400 mt-0.5">Latest activity across all monitoring systems</p>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b border-gray-100">
                                    <th className="text-left py-3 px-6 font-semibold text-gray-400 text-[10px] uppercase tracking-wider">Date</th>
                                    <th className="text-left py-3 px-6 font-semibold text-gray-400 text-[10px] uppercase tracking-wider">Category</th>
                                    <th className="text-left py-3 px-6 font-semibold text-gray-400 text-[10px] uppercase tracking-wider">Description</th>
                                    <th className="text-right py-3 px-6 font-semibold text-gray-400 text-[10px] uppercase tracking-wider">Total</th>
                                    <th className="text-center py-3 px-6 font-semibold text-gray-400 text-[10px] uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {recentTransactions && recentTransactions.length > 0 ? (
                                    recentTransactions.map((payment, index) => {
                                        const getDetailUrl = () => {
                                            const sheetName = `${payment.month} ${payment.year}`;
                                            if (payment.category === 'BFKO') return `/bfko?sheet=${encodeURIComponent(sheetName)}`;
                                            if (payment.category === 'CC Card') return `/cc-card?sheet=${encodeURIComponent(sheetName)}`;
                                            if (payment.category === 'Service Fee') return `/service-fee?sheet=${encodeURIComponent(sheetName)}`;
                                            if (payment.category === 'SPPD') return `/sppd?sheet=${encodeURIComponent(sheetName)}`;
                                            return '#';
                                        };

                                        const CategoryIcon = categoryIcons[payment.category] || Icons.Receipt;
                                        const colorMap = {
                                            'BFKO': { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200' },
                                            'CC Card': { bg: 'bg-sky-50', text: 'text-sky-700', border: 'border-sky-200' },
                                            'SPPD': { bg: 'bg-rose-50', text: 'text-rose-700', border: 'border-rose-200' },
                                            'Service Fee': { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200' },
                                        };
                                        const colors = colorMap[payment.category] || { bg: 'bg-gray-50', text: 'text-gray-700', border: 'border-gray-200' };

                                        return (
                                            <tr key={index}
                                                className="border-b border-gray-50 hover:bg-gray-50/50 transition-colors cursor-pointer"
                                                onClick={() => router.visit(getDetailUrl())}>
                                                <td className="py-3.5 px-6 text-gray-500 text-xs font-medium">{payment.date}</td>
                                                <td className="py-3.5 px-6">
                                                    <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-semibold
                                                        ${colors.bg} ${colors.text} border ${colors.border}`}>
                                                        <CategoryIcon className="w-3 h-3" />
                                                        {payment.category}
                                                    </span>
                                                </td>
                                                <td className="py-3.5 px-6">
                                                    <div className="flex flex-col">
                                                        <span className="text-xs text-gray-700 font-medium">{payment.description}</span>
                                                        <span className="text-[10px] text-gray-400 mt-0.5">{payment.count} records</span>
                                                    </div>
                                                </td>
                                                <td className="py-3.5 px-6 font-bold text-gray-900 text-right text-xs font-display">{payment.total}</td>
                                                <td className="py-3.5 px-6 text-center">
                                                    <span className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-semibold ${payment.status === 'Complete' || payment.status === 'Lunas'
                                                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                                            : payment.status === 'Active'
                                                                ? 'bg-sky-50 text-sky-700 border border-sky-200'
                                                                : 'bg-gray-50 text-gray-600 border border-gray-200'
                                                        }`}>
                                                        <Icons.Check className="w-3 h-3" />
                                                        {payment.status}
                                                    </span>
                                                </td>
                                            </tr>
                                        );
                                    })
                                ) : (
                                    <tr>
                                        <td colSpan="5" className="py-12 text-center text-gray-400 text-sm">
                                            No recent transactions found
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* View All Links */}
                    <div className="px-6 py-4 bg-gray-50/50 border-t border-gray-100">
                        <div className="flex items-center justify-center gap-5">
                            {[
                                { href: '/bfko', label: 'BFKO', color: 'text-amber-600 hover:text-amber-700' },
                                { href: '/cc-card', label: 'CC Card', color: 'text-sky-600 hover:text-sky-700' },
                                { href: '/service-fee', label: 'Service Fee', color: 'text-emerald-600 hover:text-emerald-700' },
                                { href: '/sppd', label: 'SPPD', color: 'text-rose-600 hover:text-rose-700' },
                            ].map((link, i) => (
                                <React.Fragment key={link.href}>
                                    {i > 0 && <span className="text-gray-200">|</span>}
                                    <Link href={link.href} className={`text-xs font-semibold ${link.color} transition flex items-center gap-1.5 cursor-pointer`}>
                                        View {link.label}
                                        <Icons.ArrowRight className="w-3 h-3" />
                                    </Link>
                                </React.Fragment>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}
