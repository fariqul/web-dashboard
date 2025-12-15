import React, { useState, useEffect } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';
import toast, { Toaster } from 'react-hot-toast';

// Helper function to format Rupiah
const formatRupiah = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
};

// Helper function to format Rupiah Short (for charts)
const formatRupiahShort = (value) => {
    if (value >= 1000000000) {
        return `Rp ${(value / 1000000000).toFixed(1)}M`; // Miliar
    } else if (value >= 1000000) {
        return `Rp ${(value / 1000000).toFixed(1)}Jt`;
    } else if (value >= 1000) {
        return `Rp ${(value / 1000).toFixed(0)}Rb`;
    }
    return formatRupiah(value);
};

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

// Custom Tooltip Component
const CustomTooltip = ({ active, payload }) => {
    if (active && payload && payload.length) {
        return (
            <div className="bg-white p-3 border border-gray-300 rounded shadow-lg">
                <p className="font-semibold text-gray-800">{payload[0].payload.bulan}</p>
                <p className="text-blue-600">
                    Total: {formatRupiah(payload[0].value)}
                </p>
            </div>
        );
    }
    return null;
};

export default function BfkoMonitoring({ filters, years, summary, monthlyData, topEmployees, allEmployees, flash }) {
    const [selectedBulan, setSelectedBulan] = useState(filters.bulan);
    const [selectedTahun, setSelectedTahun] = useState(filters.tahun);
    const [showImportModal, setShowImportModal] = useState(false);
    const [uploadFile, setUploadFile] = useState(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [showAllEmployees, setShowAllEmployees] = useState(false);
    const [showExportMenu, setShowExportMenu] = useState(false);
    
    // Delete Employee Modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState({ nip: '', nama: '' });
    const [deleteYear, setDeleteYear] = useState('all');
    
    // CRUD Modal states
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingPayment, setEditingPayment] = useState(null);
    const [formData, setFormData] = useState({
        nip: '',
        nama: '',
        jabatan: '',
        unit: '',
        bulan: 'Januari',
        tahun: new Date().getFullYear().toString(),
        nilai_angsuran: '',
        tanggal_bayar: '',
        status_angsuran: 'Cicilan'
    });

    const bulanList = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    // Show flash messages
    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    const handleFilterChange = (bulan, tahun) => {
        router.get('/bfko', {
            bulan: bulan,
            tahun: tahun
        }, {
            preserveState: true,
            preserveScroll: true
        });
    };

    const handleImport = (e) => {
        e.preventDefault();
        
        if (!uploadFile) {
            toast.error('Pilih file terlebih dahulu!');
            return;
        }

        const formData = new FormData();
        formData.append('file', uploadFile);

        router.post('/bfko/import', formData, {
            onSuccess: (page) => {
                toast.success('Import berhasil!');
                setShowImportModal(false);
                setUploadFile(null);
            },
            onError: (errors) => {
                toast.error('Import gagal: ' + Object.values(errors).join(', '));
            }
        });
    };

    const handleDeleteAll = () => {
        if (confirm('Apakah Anda yakin ingin menghapus SEMUA data BFKO? Tindakan ini tidak dapat dibatalkan!')) {
            router.delete('/bfko/delete-all', {
                onSuccess: () => {
                    toast.success('Semua data berhasil dihapus');
                },
                onError: () => {
                    toast.error('Gagal menghapus data');
                }
            });
        }
    };

    // CRUD Handlers
    const handleSubmit = (e) => {
        e.preventDefault();
        
        if (editingPayment) {
            // Update existing
            router.put(`/bfko/payment/${editingPayment.id}`, formData, {
                onSuccess: () => {
                    toast.success('Data berhasil diupdate!');
                    setIsModalOpen(false);
                    setEditingPayment(null);
                },
                onError: (errors) => {
                    toast.error('Gagal update: ' + Object.values(errors).join(', '));
                }
            });
        } else {
            // Create new
            router.post('/bfko/payment/store', formData, {
                onSuccess: () => {
                    toast.success('Data berhasil ditambahkan!');
                    setIsModalOpen(false);
                },
                onError: (errors) => {
                    toast.error('Gagal menambah: ' + Object.values(errors).join(', '));
                }
            });
        }
    };

    const handleEdit = (employee) => {
        setEditingPayment(employee);
        setFormData({
            nip: employee.nip,
            nama: employee.nama,
            jabatan: employee.jabatan,
            unit: employee.unit,
            bulan: employee.bulan,
            tahun: employee.tahun.toString(),
            nilai_angsuran: employee.nilai_angsuran,
            tanggal_bayar: employee.tanggal_bayar || '',
            status_angsuran: employee.status_angsuran || 'Cicilan'
        });
        setIsModalOpen(true);
    };

    const handleDelete = (id) => {
        if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            router.delete(`/bfko/payment/${id}`, {
                onSuccess: () => {
                    toast.success('Data berhasil dihapus!');
                },
                onError: () => {
                    toast.error('Gagal menghapus data');
                }
            });
        }
    };

    const handleDeleteEmployee = (nip, nama) => {
        setDeleteTarget({ nip, nama });
        setDeleteYear('all');
        setShowDeleteModal(true);
    };

    const confirmDeleteEmployee = () => {
        const yearParam = deleteYear === 'all' ? '' : `?year=${deleteYear}`;
        router.delete(`/bfko/employee/${deleteTarget.nip}${yearParam}`, {
            onSuccess: () => {
                toast.success(deleteYear === 'all' 
                    ? 'Semua data pegawai berhasil dihapus!' 
                    : `Data pegawai tahun ${deleteYear} berhasil dihapus!`
                );
                setShowDeleteModal(false);
            },
            onError: () => {
                toast.error('Gagal menghapus data pegawai');
            }
        });
    };

    // Sort monthly data by month order
    const sortedMonthlyData = [...monthlyData].sort((a, b) => {
        const monthOrder = {
            'Januari': 1, 'Februari': 2, 'Maret': 3, 'April': 4,
            'Mei': 5, 'Juni': 6, 'Juli': 7, 'Agustus': 8,
            'September': 9, 'Oktober': 10, 'November': 11, 'Desember': 12
        };
        return (monthOrder[a.bulan] || 99) - (monthOrder[b.bulan] || 99);
    });

    // Filter and display employees
    const employeesToDisplay = showAllEmployees 
        ? (allEmployees || topEmployees)
        : topEmployees;

    const filteredEmployees = searchQuery
        ? employeesToDisplay.filter(emp => 
            emp.nama.toLowerCase().includes(searchQuery.toLowerCase()) ||
            emp.nip.toLowerCase().includes(searchQuery.toLowerCase())
          )
        : employeesToDisplay;

    return (
        <MainLayout>
            <Head title="BFKO Monitoring" />
            <Toaster position="top-right" />

            {/* Header */}
            <div className="bg-gradient-to-r from-blue-600 via-cyan-500 to-teal-500 text-white p-8 shadow-lg mb-8">
                <div className="max-w-7xl mx-auto">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="bg-white/20 backdrop-blur-sm p-4 rounded-2xl">
                                <svg className="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <h1 className="text-4xl font-extrabold tracking-tight">BFKO Monitoring</h1>
                                <p className="text-blue-100 mt-1 text-sm font-medium">Bantuan Fasilitas Kendaraan Operasional</p>
                            </div>
                        </div>
                        
                        <div className="flex gap-3">
                            <button
                                onClick={() => setShowImportModal(true)}
                                className="px-5 py-3 bg-white/20 backdrop-blur-md hover:bg-white/30 rounded-xl font-semibold transition-all duration-300 flex items-center gap-2 shadow-lg hover:shadow-xl hover:scale-105"
                            >
                                <span className="text-2xl">📥</span>
                                <span>Import Data</span>
                            </button>
                            <button
                                onClick={handleDeleteAll}
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
                    {/* Search & Filter Bar with Add Button */}
                    <div className="bg-white rounded-2xl shadow-xl p-6 mb-8 border border-gray-100">
                        <div className="flex flex-wrap gap-4 items-center justify-between mb-4">
                            <h2 className="text-2xl font-bold text-gray-800">Data BFKO</h2>
                            <button
                                onClick={() => {
                                    setIsModalOpen(true);
                                    setEditingPayment(null);
                                    setFormData({
                                        nip: '',
                                        nama: '',
                                        jabatan: '',
                                        unit: '',
                                        bulan: 'Januari',
                                        tahun: new Date().getFullYear().toString(),
                                        nilai_angsuran: '',
                                        tanggal_bayar: '',
                                        status_angsuran: 'Cicilan'
                                    });
                                }}
                                className="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2 hover:scale-105"
                            >
                                <span className="text-xl">➕</span>
                                <span>Tambah Data</span>
                            </button>
                        </div>
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
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        placeholder="🔍 Cari pegawai berdasarkan nama atau NIP..."
                                        className="w-full pl-12 pr-4 py-3 bg-cyan-50 border-2 border-cyan-200 rounded-xl focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100 transition-all"
                                    />
                                </div>
                            </div>
                            
                            <div className="relative">
                                <select
                                    value={selectedBulan}
                                    onChange={(e) => {
                                        setSelectedBulan(e.target.value);
                                        handleFilterChange(e.target.value, selectedTahun);
                                    }}
                                    className="appearance-none px-6 py-3 pr-10 bg-gradient-to-r from-cyan-500 to-blue-500 text-white border-0 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all cursor-pointer focus:ring-4 focus:ring-cyan-200"
                                    style={{ color: 'white' }}
                                >
                                    <option value="all" className="text-gray-900 bg-white">📅 Semua Bulan</option>
                                    {bulanList.map(bulan => (
                                        <option key={bulan} value={bulan} className="text-gray-900 bg-white">{bulan}</option>
                                    ))}
                                </select>
                                <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-white">
                                    <svg className="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                    </svg>
                                </div>
                            </div>
                            
                            <div className="relative">
                                <select
                                    value={selectedTahun}
                                    onChange={(e) => {
                                        setSelectedTahun(e.target.value);
                                        handleFilterChange(selectedBulan, e.target.value);
                                    }}
                                    className="appearance-none px-6 py-3 pr-10 bg-gradient-to-r from-purple-500 to-pink-500 text-white border-0 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all cursor-pointer focus:ring-4 focus:ring-purple-200"
                                    style={{ color: 'white' }}
                                >
                                    <option value="all" className="text-gray-900 bg-white">📆 Semua Tahun</option>
                                    {years.map(year => (
                                        <option key={year} value={year} className="text-gray-900 bg-white">{year}</option>
                                    ))}
                                </select>
                                <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-white">
                                    <svg className="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Summary Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        {/* Total Pembayaran Card */}
                        <div className="group relative overflow-hidden bg-gradient-to-br from-blue-500 via-blue-600 to-cyan-600 rounded-3xl shadow-2xl hover:shadow-3xl transition-all duration-500 hover:scale-105">
                            <div className="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -mr-20 -mt-20 group-hover:scale-150 transition-transform duration-700"></div>
                            <div className="relative p-8 text-white">
                                <div className="flex items-start justify-between mb-4">
                                    <div className="bg-white/20 backdrop-blur-sm p-4 rounded-2xl group-hover:rotate-12 transition-transform duration-500">
                                        <svg className="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-sm font-medium text-blue-100 mb-1">Total Pembayaran</div>
                                        <div className="text-3xl font-black tracking-tight">
                                            Rp {formatSummaryDisplay(summary.totalPayments).value}{formatSummaryDisplay(summary.totalPayments).unit}
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-4 pt-4 border-t border-white/20">
                                    <div className="text-sm text-blue-50">{formatRupiah(summary.totalPayments)}</div>
                                </div>
                            </div>
                        </div>

                        {/* Total Transaksi Card */}
                        <div className="group relative overflow-hidden bg-gradient-to-br from-green-500 via-emerald-600 to-teal-600 rounded-3xl shadow-2xl hover:shadow-3xl transition-all duration-500 hover:scale-105">
                            <div className="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -mr-20 -mt-20 group-hover:scale-150 transition-transform duration-700"></div>
                            <div className="relative p-8 text-white">
                                <div className="flex items-start justify-between mb-4">
                                    <div className="bg-white/20 backdrop-blur-sm p-4 rounded-2xl group-hover:rotate-12 transition-transform duration-500">
                                        <svg className="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-sm font-medium text-green-100 mb-1">Total Transaksi</div>
                                        <div className="text-5xl font-black">
                                            {summary.totalRecords}
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-4 pt-4 border-t border-white/20">
                                    <div className="text-sm text-green-50">Pembayaran Tercatat</div>
                                </div>
                            </div>
                        </div>

                        {/* Total Pegawai Card */}
                        <div className="group relative overflow-hidden bg-gradient-to-br from-purple-500 via-pink-600 to-rose-600 rounded-3xl shadow-2xl hover:shadow-3xl transition-all duration-500 hover:scale-105">
                            <div className="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -mr-20 -mt-20 group-hover:scale-150 transition-transform duration-700"></div>
                            <div className="relative p-8 text-white">
                                <div className="flex items-start justify-between mb-4">
                                    <div className="bg-white/20 backdrop-blur-sm p-4 rounded-2xl group-hover:rotate-12 transition-transform duration-500">
                                        <svg className="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-sm font-medium text-purple-100 mb-1">Total Pegawai</div>
                                        <div className="text-5xl font-black">
                                            {summary.totalEmployees}
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-4 pt-4 border-t border-white/20">
                                    <div className="text-sm text-purple-50">Penerima BFKO Aktif</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Charts Section - Two Columns */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        {/* Monthly Chart */}
                        <div className="bg-white rounded-xl shadow-lg p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <span className="text-3xl">📊</span>
                                <div>
                                    <h3 className="text-lg font-bold text-gray-800">Pembayaran per Bulan</h3>
                                    <p className="text-sm text-gray-500">Trend pembayaran bulanan BFKO</p>
                                </div>
                            </div>
                            <ResponsiveContainer width="100%" height={350}>
                                <BarChart data={sortedMonthlyData} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
                                    <defs>
                                        <linearGradient id="colorTotal" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#3b82f6" stopOpacity={0.9}/>
                                            <stop offset="95%" stopColor="#06b6d4" stopOpacity={0.7}/>
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" />
                                    <XAxis 
                                        dataKey="bulan" 
                                        tick={{ fill: '#6B7280', fontSize: 12 }}
                                        angle={-45}
                                        textAnchor="end"
                                        height={80}
                                    />
                                    <YAxis 
                                        tickFormatter={(value) => formatRupiahShort(value)}
                                        tick={{ fill: '#6B7280', fontSize: 12 }}
                                        width={80}
                                    />
                                    <Tooltip content={<CustomTooltip />} />
                                    <Legend 
                                        wrapperStyle={{ paddingTop: '10px' }}
                                        iconType="circle"
                                    />
                                    <Bar 
                                        dataKey="total" 
                                        fill="url(#colorTotal)" 
                                        name="Total Pembayaran"
                                        radius={[8, 8, 0, 0]}
                                    />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>

                        {/* Top 5 Employees Pie Chart */}
                        <div className="bg-white rounded-xl shadow-lg p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <span className="text-3xl">🥇</span>
                                <div>
                                    <h3 className="text-lg font-bold text-gray-800">Top 5 Pegawai</h3>
                                    <p className="text-sm text-gray-500">Distribusi pembayaran terbesar</p>
                                </div>
                            </div>
                            <ResponsiveContainer width="100%" height={350}>
                                <PieChart>
                                    <Pie
                                        data={topEmployees.slice(0, 5).map((emp, idx) => ({
                                            name: emp.nama.split(' ')[0],
                                            fullName: emp.nama,
                                            value: emp.total,
                                            nip: emp.nip
                                        }))}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={({ name, percent }) => `${name} (${(percent * 100).toFixed(0)}%)`}
                                        outerRadius={100}
                                        fill="#8884d8"
                                        dataKey="value"
                                    >
                                        {topEmployees.slice(0, 5).map((entry, index) => {
                                            const colors = ['#3b82f6', '#06b6d4', '#8b5cf6', '#ec4899', '#f59e0b'];
                                            return <Cell key={`cell-${index}`} fill={colors[index % colors.length]} />;
                                        })}
                                    </Pie>
                                    <Tooltip 
                                        formatter={(value) => formatRupiah(value)}
                                        contentStyle={{
                                            backgroundColor: '#fff',
                                            border: '1px solid #e5e7eb',
                                            borderRadius: '8px',
                                            padding: '8px 12px'
                                        }}
                                    />
                                    <Legend 
                                        verticalAlign="bottom" 
                                        height={36}
                                        formatter={(value, entry) => `${value} - ${formatRupiahShort(entry.payload.value)}`}
                                    />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>
                    </div>

                    {/* Top Employees Table */}
                    <div className="bg-white rounded-lg shadow-sm p-6">
                        <div className="flex items-center justify-between mb-6">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-800">
                                    {showAllEmployees ? 'Semua Pegawai' : 'Top 10 Pegawai'}
                                </h3>
                                <p className="text-sm text-gray-500 mt-1">
                                    {filteredEmployees.length} pegawai ditampilkan
                                    {searchQuery && ` (dari pencarian: "${searchQuery}")`}
                                </p>
                            </div>
                            <div className="flex gap-3">
                                {/* Export Dropdown */}
                                <div className="relative">
                                    <button
                                        onClick={() => setShowExportMenu(!showExportMenu)}
                                        className="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2 hover:scale-105"
                                    >
                                        <span className="text-xl">📥</span>
                                        <span>Export</span>
                                        <svg className={`w-4 h-4 transition-transform ${
                                            showExportMenu ? 'rotate-180' : ''
                                        }`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    {showExportMenu && (
                                        <div className="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl z-10 border border-gray-200 overflow-hidden">
                                            <button
                                                onClick={() => {
                                                    window.location.href = `/bfko/export/pdf?tahun=${selectedTahun}`;
                                                    setShowExportMenu(false);
                                                }}
                                                className="w-full px-5 py-3 text-left hover:bg-red-50 transition-all flex items-center gap-3 border-b border-gray-100"
                                            >
                                                <span className="text-2xl">📄</span>
                                                <div>
                                                    <div className="font-semibold text-gray-800">Export PDF</div>
                                                    <div className="text-xs text-gray-500">Format dokumen PDF</div>
                                                </div>
                                            </button>
                                            <button
                                                onClick={() => {
                                                    window.location.href = `/bfko/export/excel?tahun=${selectedTahun}`;
                                                    setShowExportMenu(false);
                                                }}
                                                className="w-full px-5 py-3 text-left hover:bg-green-50 transition-all flex items-center gap-3"
                                            >
                                                <span className="text-2xl">📊</span>
                                                <div>
                                                    <div className="font-semibold text-gray-800">Export Excel</div>
                                                    <div className="text-xs text-gray-500">Format spreadsheet XLSX</div>
                                                </div>
                                            </button>
                                        </div>
                                    )}
                                </div>
                                <button
                                    onClick={() => setShowAllEmployees(!showAllEmployees)}
                                    className={`px-6 py-3 rounded-xl font-semibold shadow-lg transition-all duration-300 ${
                                        showAllEmployees
                                            ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white hover:shadow-xl'
                                            : 'bg-gradient-to-r from-purple-500 to-pink-500 text-white hover:shadow-xl'
                                    }`}
                                >
                                    {showAllEmployees ? '📊 Tampilkan Top 10' : '👥 Tampilkan Semua'}
                                </button>
                            </div>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Rank
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            NIP
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nama
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Jabatan
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Total Pembayaran
                                        </th>
                                        <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {filteredEmployees.map((employee, index) => (
                                        <tr key={employee.nip} className="hover:bg-blue-50 transition-colors cursor-pointer">
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {index + 1}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {employee.nip}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {employee.nama}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {employee.jabatan}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900">
                                                {formatRupiah(employee.total)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-center">
                                                <div className="flex items-center justify-center gap-2">
                                                    <Link
                                                        href={`/bfko/employee/${employee.nip}?tahun=${selectedTahun}`}
                                                        className="px-3 py-1.5 bg-gradient-to-r from-blue-500 to-cyan-500 text-white text-xs font-semibold rounded-lg hover:shadow-lg transition-all duration-300 hover:scale-105 inline-block"
                                                    >
                                                        📋 Detail
                                                    </Link>
                                                    <button
                                                        onClick={() => handleDeleteEmployee(employee.nip, employee.nama)}
                                                        className="px-3 py-1.5 bg-gradient-to-r from-red-500 to-red-600 text-white text-xs font-semibold rounded-lg hover:shadow-lg transition-all duration-300 hover:scale-105"
                                                        title="Hapus semua data pegawai ini"
                                                    >
                                                        🗑️ Hapus
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {filteredEmployees.length === 0 && (
                                        <tr>
                                            <td colSpan="6" className="px-6 py-8 text-center text-gray-500">
                                                {searchQuery 
                                                    ? `Tidak ada pegawai yang cocok dengan pencarian "${searchQuery}"`
                                                    : 'Tidak ada data'
                                                }
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {/* Import Modal */}
                {showImportModal && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div className="bg-white rounded-lg p-6 max-w-lg w-full mx-4">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Import Data BFKO
                            </h3>
                            <div className="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <p className="text-sm text-blue-800 font-semibold mb-2">📋 Format yang Didukung:</p>
                                <code className="text-xs text-blue-900 block bg-white p-2 rounded mt-1 overflow-x-auto">
                                    nip,nama,jabatan,unit,bulan,tahun,nilai_angsuran,tanggal_bayar,status_angsuran
                                </code>
                                <p className="text-xs text-blue-700 mt-2">
                                    ✅ Upload CSV atau Excel - Otomatis convert!<br/>
                                    ✅ Support format Excel asli dari SAP<br/>
                                    ✅ Semua data employee + pembayaran dalam 1 file<br/>
                                    ✅ Otomatis update jika data sudah ada
                                </p>
                            </div>
                            <form onSubmit={handleImport}>
                                <div className="mb-4">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        File CSV atau Excel
                                    </label>
                                    <input
                                        type="file"
                                        accept=".csv,.xlsx,.xls"
                                        onChange={(e) => setUploadFile(e.target.files[0])}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Upload Excel asli atau CSV yang sudah diformat
                                    </p>
                                </div>
                                <div className="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setShowImportModal(false);
                                            setUploadFile(null);
                                        }}
                                        className="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="submit"
                                        className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                                    >
                                        Import
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
                
                {/* CRUD Modal */}
                {isModalOpen && (
                    <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                        <div className="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                            <div className="sticky top-0 bg-gradient-to-r from-blue-600 to-cyan-600 text-white p-6 rounded-t-2xl">
                                <h3 className="text-2xl font-bold">
                                    {editingPayment ? '✏️ Edit Data BFKO' : '➕ Tambah Data BFKO'}
                                </h3>
                            </div>
                            <form onSubmit={handleSubmit} className="p-6 space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-700 mb-2">NIP *</label>
                                        <input
                                            type="text"
                                            value={formData.nip}
                                            onChange={(e) => setFormData({...formData, nip: e.target.value})}
                                            required
                                            className="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all"
                                            placeholder="Contoh: 7194010G"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-700 mb-2">Nama Pegawai *</label>
                                        <input
                                            type="text"
                                            value={formData.nama}
                                            onChange={(e) => setFormData({...formData, nama: e.target.value})}
                                            required
                                            className="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all"
                                            placeholder="Nama lengkap"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-700 mb-2">Jabatan</label>
                                        <input
                                            type="text"
                                            value={formData.jabatan}
                                            onChange={(e) => setFormData({...formData, jabatan: e.target.value})}
                                            className="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all"
                                            placeholder="Contoh: Manager"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-700 mb-2">Unit</label>
                                        <input
                                            type="text"
                                            value={formData.unit}
                                            onChange={(e) => setFormData({...formData, unit: e.target.value})}
                                            className="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all"
                                            placeholder="Contoh: UP3 Makassar"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-700 mb-2">Bulan *</label>
                                        <select
                                            value={formData.bulan}
                                            onChange={(e) => setFormData({...formData, bulan: e.target.value})}
                                            required
                                            className="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all"
                                        >
                                            {bulanList.map(bulan => (
                                                <option key={bulan} value={bulan}>{bulan}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-700 mb-2">Tahun *</label>
                                        <input
                                            type="number"
                                            value={formData.tahun}
                                            onChange={(e) => setFormData({...formData, tahun: e.target.value})}
                                            required
                                            className="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all"
                                            placeholder="2025"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">Nilai Angsuran (Rp) *</label>
                                    <input
                                        type="number"
                                        value={formData.nilai_angsuran}
                                        onChange={(e) => setFormData({...formData, nilai_angsuran: e.target.value})}
                                        required
                                        className="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all"
                                        placeholder="Contoh: 2987484"
                                    />
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-700 mb-2">Tanggal Bayar</label>
                                        <input
                                            type="date"
                                            value={formData.tanggal_bayar}
                                            onChange={(e) => setFormData({...formData, tanggal_bayar: e.target.value})}
                                            className="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-700 mb-2">Status Angsuran</label>
                                        <select
                                            value={formData.status_angsuran}
                                            onChange={(e) => setFormData({...formData, status_angsuran: e.target.value})}
                                            className="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all"
                                        >
                                            <option value="Lunas">Lunas</option>
                                            <option value="Cicilan">Cicilan</option>
                                            <option value="Angsuran Ke-24">Angsuran Ke-24</option>
                                        </select>
                                    </div>
                                </div>

                                <div className="flex justify-end gap-3 pt-4 border-t">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setIsModalOpen(false);
                                            setEditingPayment(null);
                                        }}
                                        className="px-6 py-2.5 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 font-semibold transition-all"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="submit"
                                        className="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-lg hover:shadow-lg font-semibold transition-all"
                                    >
                                        {editingPayment ? 'Update' : 'Simpan'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* Delete Employee Confirmation Modal */}
                {showDeleteModal && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                        <div className="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all">
                            {/* Header */}
                            <div className="bg-gradient-to-r from-red-500 to-rose-500 text-white px-6 py-4 rounded-t-2xl">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="bg-white/20 p-2 rounded-lg">
                                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <h2 className="text-xl font-bold">Konfirmasi Hapus Pegawai</h2>
                                    </div>
                                    <button
                                        onClick={() => setShowDeleteModal(false)}
                                        className="text-white hover:bg-white/20 rounded-lg p-1 transition-all"
                                    >
                                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {/* Body */}
                            <div className="p-6">
                                <div className="mb-6">
                                    <div className="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg mb-4">
                                        <div className="flex">
                                            <div className="flex-shrink-0">
                                                <svg className="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                </svg>
                                            </div>
                                            <div className="ml-3">
                                                <p className="text-sm font-semibold text-red-800">
                                                    Perhatian! Tindakan ini tidak dapat dibatalkan.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <p className="text-gray-700 mb-4">
                                        Anda akan menghapus data pegawai:
                                    </p>
                                    <div className="bg-gray-50 rounded-lg p-4 mb-4">
                                        <p className="font-bold text-gray-900 text-lg">{deleteTarget.nama}</p>
                                        <p className="text-sm text-gray-600">NIP: {deleteTarget.nip}</p>
                                    </div>

                                    <div className="mb-4">
                                        <label className="block text-sm font-semibold text-gray-700 mb-2">
                                            Pilih Tahun yang akan Dihapus:
                                        </label>
                                        <select
                                            value={deleteYear}
                                            onChange={(e) => setDeleteYear(e.target.value)}
                                            className="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-200 transition-all font-semibold"
                                        >
                                            <option value="all">🗑️ Semua Tahun (Hapus Total)</option>
                                            {years.map(year => (
                                                <option key={year} value={year}>
                                                    📅 Tahun {year} Saja
                                                </option>
                                            ))}
                                        </select>
                                        <p className="text-xs text-gray-500 mt-2">
                                            {deleteYear === 'all' 
                                                ? '⚠️ Semua data pembayaran pegawai ini akan dihapus secara permanen'
                                                : `⚠️ Hanya data pembayaran tahun ${deleteYear} yang akan dihapus`
                                            }
                                        </p>
                                    </div>
                                </div>

                                {/* Action Buttons */}
                                <div className="flex gap-3">
                                    <button
                                        onClick={() => setShowDeleteModal(false)}
                                        className="flex-1 px-4 py-3 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 font-semibold transition-all"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        onClick={confirmDeleteEmployee}
                                        className="flex-1 px-4 py-3 bg-gradient-to-r from-red-500 to-rose-500 text-white rounded-lg hover:shadow-lg font-semibold transition-all"
                                    >
                                        Hapus {deleteYear === 'all' ? 'Semua' : `Tahun ${deleteYear}`}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </MainLayout>
    );
}
