import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';
import { PieChart, Pie, Cell, BarChart, Bar, XAxis, YAxis, CartesianGrid, ResponsiveContainer, Tooltip, Legend } from 'recharts';
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

// Helper function to format date
const formatDate = (dateString) => {
    if (!dateString || dateString === '-') return '-';
    
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        
        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        }).format(date);
    } catch (e) {
        return dateString;
    }
};

// Custom Tooltip for Bar Chart
const CustomBarTooltip = ({ active, payload }) => {
    if (active && payload && payload.length) {
        return (
            <div className="bg-white p-3 border border-gray-300 rounded-lg shadow-lg">
                <p className="font-semibold text-gray-800">{payload[0].payload.month}</p>
                <p className="text-green-600 font-medium">
                    Total: {formatRupiah(payload[0].payload.amount * 1000000)}
                </p>
            </div>
        );
    }
    return null;
};

// Custom Tooltip for Pie Chart
const CustomPieTooltip = ({ active, payload }) => {
    if (active && payload && payload.length) {
        return (
            <div className="bg-white p-3 border border-gray-300 rounded-lg shadow-lg">
                <p className="font-semibold text-gray-800">{payload[0].name}</p>
                <p className="text-blue-600 font-medium">
                    {payload[0].value} transaksi
                </p>
            </div>
        );
    }
    return null;
};

export default function BfkoEmployeeDetail({ employee, payments, availableYears = [], selectedYear = 'all' }) {
    const [showModal, setShowModal] = useState(false);
    const [editingPayment, setEditingPayment] = useState(null);
    const [selectedTahun, setSelectedTahun] = useState(selectedYear);
    const [formData, setFormData] = useState({
        bulan: '',
        tahun: new Date().getFullYear(),
        nilai_angsuran: '',
        tanggal_bayar: '',
        status_angsuran: ''
    });

    const bulanList = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    // Calculate progress statistics based on status_angsuran
    // Complete = status is "Lunas" OR has tanggal_bayar
    // In Progress = status is "Cicilan" OR no tanggal_bayar
    const completedPayments = payments.filter(p => {
        // Prioritas: status_angsuran, lalu tanggal_bayar
        if (p.status_angsuran) {
            return p.status_angsuran === 'Lunas';
        }
        return p.tanggal_bayar && p.tanggal_bayar !== '-';
    }).length;
    
    const inProgressPayments = payments.filter(p => {
        // Prioritas: status_angsuran, lalu tanggal_bayar
        if (p.status_angsuran) {
            return p.status_angsuran === 'Cicilan';
        }
        return !p.tanggal_bayar || p.tanggal_bayar === '-';
    }).length;
    
    const totalPayments = payments.length;
    const completedPercentage = totalPayments > 0 ? Math.round((completedPayments / totalPayments) * 100) : 0;

    // Group payments by month for chart
    const monthlyPayments = {};
    payments.forEach(payment => {
        const key = `${payment.bulan.substring(0, 3)} ${payment.tahun}`;
        if (!monthlyPayments[key]) {
            monthlyPayments[key] = 0;
        }
        monthlyPayments[key] += parseFloat(payment.nilai_angsuran);
    });
    
    const monthlyChartData = Object.entries(monthlyPayments)
        .slice(-6) // Last 6 months
        .map(([month, amount]) => ({
            month,
            amount: amount / 1000000 // Convert to millions
        }));

    const progressData = [
        { name: 'Complete', value: completedPayments },
        { name: 'In Progress', value: inProgressPayments }
    ];

    const handleAddNew = () => {
        setEditingPayment(null);
        setFormData({
            bulan: '',
            tahun: new Date().getFullYear(),
            nilai_angsuran: '',
            tanggal_bayar: '',
            status_angsuran: ''
        });
        setShowModal(true);
    };

    const handleEdit = (payment) => {
        setEditingPayment(payment);
        setFormData({
            bulan: payment.bulan,
            tahun: payment.tahun,
            nilai_angsuran: payment.nilai_angsuran,
            tanggal_bayar: payment.tanggal_bayar || '',
            status_angsuran: payment.status_angsuran || ''
        });
        setShowModal(true);
    };

    const handleDelete = (paymentId) => {
        if (confirm('Apakah Anda yakin ingin menghapus pembayaran ini?')) {
            router.delete(`/bfko/payment/${paymentId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Pembayaran berhasil dihapus');
                },
                onError: () => {
                    toast.error('Gagal menghapus pembayaran');
                }
            });
        }
    };

    const handleYearChange = (tahun) => {
        setSelectedTahun(tahun);
        router.get(`/bfko/employee/${employee.nip}`, {
            tahun: tahun
        }, {
            preserveState: true,
            preserveScroll: true
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        const data = {
            ...formData,
            nip: employee.nip,
            nama: employee.nama,
            jabatan: employee.jabatan,
            unit: employee.unit
        };

        if (editingPayment) {
            // Update existing payment
            router.put(`/bfko/payment/${editingPayment.id}`, data, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Pembayaran berhasil diupdate');
                    setShowModal(false);
                },
                onError: (errors) => {
                    toast.error('Gagal update pembayaran: ' + Object.values(errors).join(', '));
                }
            });
        } else {
            // Create new payment
            router.post('/bfko/payment/store', data, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Pembayaran berhasil ditambahkan');
                    setShowModal(false);
                },
                onError: (errors) => {
                    toast.error('Gagal menambah pembayaran: ' + Object.values(errors).join(', '));
                }
            });
        }
    };

    return (
        <MainLayout>
            <Head title={`Detail - ${employee.nama}`} />
            <Toaster position="top-right" />
            
            <div className="p-8">
                {/* Back Button and Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <Link 
                            href="/bfko" 
                            className="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 font-medium mb-4 transition-colors"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                            Kembali ke BFKO Monitoring
                        </Link>
                        <h1 className="text-3xl font-bold text-gray-900">{employee.nama}</h1>
                        <p className="text-gray-600 mt-1">NIP: {employee.nip}</p>
                    </div>
                    
                    {/* Year Filter */}
                    {availableYears.length > 1 && (
                        <div className="relative">
                            <select
                                value={selectedTahun}
                                onChange={(e) => handleYearChange(e.target.value)}
                                className="appearance-none px-6 py-3 pr-10 bg-gradient-to-r from-purple-500 to-pink-500 text-white border-0 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all cursor-pointer focus:ring-4 focus:ring-purple-200"
                                style={{ color: 'white' }}
                            >
                                <option value="all" className="text-gray-900 bg-white">📆 Semua Tahun</option>
                                {availableYears.map(year => (
                                    <option key={year} value={year} className="text-gray-900 bg-white">{year}</option>
                                ))}
                            </select>
                            <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-white">
                                <svg className="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                </svg>
                            </div>
                        </div>
                    )}
                </div>

                {/* Employee Info Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div className="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-6 border border-blue-200">
                        <div className="text-sm text-gray-600 mb-2">Jabatan</div>
                        <div className="text-xl font-bold text-gray-900">{employee.jabatan}</div>
                    </div>
                    <div className="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
                        <div className="text-sm text-gray-600 mb-2">Unit</div>
                        <div className="text-xl font-bold text-gray-900">{employee.unit || '-'}</div>
                    </div>
                    <div className="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border border-purple-200">
                        <div className="text-sm text-gray-600 mb-2">Total Pembayaran</div>
                        <div className="text-xl font-bold text-gray-900">{formatRupiah(employee.total)}</div>
                    </div>
                </div>

                {/* Charts Section */}
                <div className="bg-gray-100 rounded-xl p-6 mb-6">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        {/* Progress Pie Chart */}
                        <div className="flex items-center justify-center gap-8">
                            <div className="relative w-48 h-48">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={progressData}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={60}
                                            outerRadius={85}
                                            paddingAngle={2}
                                            dataKey="value"
                                            startAngle={90}
                                            endAngle={-270}
                                        >
                                            <Cell fill="#22c55e" />
                                            <Cell fill="#fbbf24" />
                                        </Pie>
                                        <Tooltip content={<CustomPieTooltip />} />
                                    </PieChart>
                                </ResponsiveContainer>
                                <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                    <p className="text-4xl font-bold text-center text-gray-900">{completedPercentage}%</p>
                                </div>
                            </div>
                            <div className="space-y-4">
                                <div className="flex items-center gap-3">
                                    <div className="w-4 h-4 bg-green-500 rounded-full"></div>
                                    <span className="text-gray-700 font-medium">Complete ({completedPayments})</span>
                                </div>
                                <div className="flex items-center gap-3">
                                    <div className="w-4 h-4 bg-yellow-400 rounded-full"></div>
                                    <span className="text-gray-700 font-medium">In Progress ({inProgressPayments})</span>
                                </div>
                            </div>
                        </div>

                        {/* Monthly Payment Bar Chart */}
                        <div>
                            <h3 className="text-lg font-bold text-gray-900 mb-4">Monthly Payment</h3>
                            {monthlyChartData.length > 0 ? (
                                <ResponsiveContainer width="100%" height={220}>
                                    <BarChart data={monthlyChartData}>
                                        <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                        <XAxis 
                                            dataKey="month" 
                                            tick={{ fill: '#6b7280', fontSize: 12 }}
                                            angle={-45}
                                            textAnchor="end"
                                            height={80}
                                        />
                                        <YAxis 
                                            tickFormatter={(value) => `${value.toFixed(1)} M`}
                                            tick={{ fill: '#6b7280', fontSize: 12 }}
                                            width={60}
                                        />
                                        <Tooltip content={<CustomBarTooltip />} />
                                        <Bar 
                                            dataKey="amount" 
                                            fill="#22c55e" 
                                            radius={[8, 8, 0, 0]}
                                            name="Pembayaran"
                                        />
                                    </BarChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="h-[220px] flex items-center justify-center text-gray-500">
                                    No payment data available
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Payment Details Table */}
                <div className="bg-white rounded-xl shadow-lg border border-gray-200">
                    <div className="p-6">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-xl font-bold text-gray-900">Payment Details</h2>
                            <div className="flex items-center gap-3">
                                <span className="text-sm text-gray-500">{payments.length} transaksi</span>
                                <button
                                    onClick={handleAddNew}
                                    className="px-4 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-semibold rounded-lg hover:shadow-lg transition-all duration-300 flex items-center gap-2"
                                >
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Pembayaran
                                </button>
                            </div>
                        </div>
                        {payments.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead>
                                        <tr className="border-b border-gray-200">
                                            <th className="text-left py-3 px-4 font-semibold text-gray-700">Date</th>
                                            <th className="text-left py-3 px-4 font-semibold text-gray-700">Description</th>
                                            <th className="text-left py-3 px-4 font-semibold text-gray-700">Periode</th>
                                            <th className="text-left py-3 px-4 font-semibold text-gray-700">Category</th>
                                            <th className="text-right py-3 px-4 font-semibold text-gray-700">Total</th>
                                            <th className="text-center py-3 px-4 font-semibold text-gray-700">Status</th>
                                            <th className="text-center py-3 px-4 font-semibold text-gray-700">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {payments.map((payment, index) => (
                                            <tr key={index} className="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                                <td className="py-4 px-4 text-gray-900">
                                                    {formatDate(payment.tanggal_bayar)}
                                                </td>
                                                <td className="py-4 px-4 text-gray-900">
                                                    Angsuran BFKO
                                                </td>
                                                <td className="py-4 px-4 text-gray-900">
                                                    {payment.bulan} {payment.tahun}
                                                </td>
                                                <td className="py-4 px-4 text-gray-900">BFKO</td>
                                                <td className="py-4 px-4 font-semibold text-gray-900 text-right">
                                                    {formatRupiah(payment.nilai_angsuran)}
                                                </td>
                                                <td className="py-4 px-4 text-center">
                                                    <span className={`inline-flex px-3 py-1 text-xs font-semibold rounded-full ${
                                                        (() => {
                                                            // Prioritas: status_angsuran, lalu tanggal_bayar
                                                            if (payment.status_angsuran) {
                                                                return payment.status_angsuran === 'Lunas' 
                                                                    ? 'bg-green-100 text-green-800'
                                                                    : 'bg-yellow-100 text-yellow-800';
                                                            }
                                                            return (payment.tanggal_bayar && payment.tanggal_bayar !== '-')
                                                                ? 'bg-green-100 text-green-800'
                                                                : 'bg-yellow-100 text-yellow-800';
                                                        })()
                                                    }`}>
                                                        {(() => {
                                                            if (payment.status_angsuran) {
                                                                return payment.status_angsuran;
                                                            }
                                                            return (payment.tanggal_bayar && payment.tanggal_bayar !== '-') 
                                                                ? 'Complete' 
                                                                : 'In Progress';
                                                        })()}
                                                    </span>
                                                </td>
                                                <td className="py-4 px-4 text-center">
                                                    <div className="flex items-center justify-center gap-2">
                                                        <button
                                                            onClick={() => handleEdit(payment)}
                                                            className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                            title="Edit"
                                                        >
                                                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        </button>
                                                        <button
                                                            onClick={() => handleDelete(payment.id)}
                                                            className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                            title="Hapus"
                                                        >
                                                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="text-center py-12 text-gray-500">
                                <svg className="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p>Tidak ada riwayat transaksi</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Modal Form */}
                {showModal && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                        <div className="bg-white rounded-2xl max-w-2xl w-full shadow-2xl">
                            <div className="p-6 border-b border-gray-200">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-2xl font-bold text-gray-900">
                                        {editingPayment ? 'Edit Pembayaran' : 'Tambah Pembayaran Baru'}
                                    </h3>
                                    <button
                                        onClick={() => setShowModal(false)}
                                        className="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-2 transition-all"
                                    >
                                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <form onSubmit={handleSubmit} className="p-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    {/* Bulan */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Bulan <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            value={formData.bulan}
                                            onChange={(e) => setFormData({ ...formData, bulan: e.target.value })}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            required
                                        >
                                            <option value="">Pilih Bulan</option>
                                            {bulanList.map(bulan => (
                                                <option key={bulan} value={bulan}>{bulan}</option>
                                            ))}
                                        </select>
                                    </div>

                                    {/* Tahun */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Tahun <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            value={formData.tahun}
                                            onChange={(e) => setFormData({ ...formData, tahun: e.target.value })}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="2025"
                                            required
                                        />
                                    </div>

                                    {/* Nilai Angsuran */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Nilai Angsuran <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            value={formData.nilai_angsuran}
                                            onChange={(e) => setFormData({ ...formData, nilai_angsuran: e.target.value })}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="1000000"
                                            required
                                        />
                                    </div>

                                    {/* Tanggal Bayar */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Tanggal Bayar
                                        </label>
                                        <input
                                            type="date"
                                            value={formData.tanggal_bayar}
                                            onChange={(e) => setFormData({ ...formData, tanggal_bayar: e.target.value })}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>

                                    {/* Status Angsuran */}
                                    <div className="md:col-span-2">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Status Angsuran
                                        </label>
                                        <select
                                            value={formData.status_angsuran}
                                            onChange={(e) => setFormData({ ...formData, status_angsuran: e.target.value })}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option value="">Pilih Status</option>
                                            <option value="Lunas">Lunas</option>
                                            <option value="Cicilan">Cicilan</option>
                                        </select>
                                    </div>
                                </div>

                                <div className="flex justify-end gap-3 mt-6">
                                    <button
                                        type="button"
                                        onClick={() => setShowModal(false)}
                                        className="px-6 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 font-medium transition-colors"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="submit"
                                        className="px-6 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-lg hover:shadow-lg font-semibold transition-all duration-300"
                                    >
                                        {editingPayment ? 'Update' : 'Simpan'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </MainLayout>
    );
}
