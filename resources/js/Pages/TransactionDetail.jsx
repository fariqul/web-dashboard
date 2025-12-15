import React from 'react';
import MainLayout from '../Layouts/MainLayout';
import { PieChart, Pie, Cell, BarChart, Bar, XAxis, YAxis, CartesianGrid, ResponsiveContainer } from 'recharts';

export default function TransactionDetail({ person = 'MOH. Andy' }) {
    const progressData = [
        { name: 'Complete', value: 90 },
        { name: 'In Progress', value: 10 }
    ];

    const monthlyData = [
        { month: 'Jan', amount: 5 },
        { month: 'Feb', amount: 5 },
        { month: 'Mar', amount: 5 },
        { month: 'Apr', amount: 4 }
    ];

    const payments = [
        {
            date: '12-08-2025',
            description: 'Angsuran BFKO',
            angsuran: 'Angsuran ke-24',
            category: 'BFKO',
            total: 'Rp10 M',
            status: 'Complete'
        },
        {
            date: '12-08-2025',
            description: 'Angsuran BFKO',
            angsuran: 'Angsuran ke-24',
            category: 'BFKO',
            total: 'Rp10 M',
            status: 'Complete'
        },
        {
            date: '',
            description: 'Angsuran BFKO',
            angsuran: 'Angsuran ke-24',
            category: 'BFKO',
            total: 'Rp10 M',
            status: 'In Progress'
        },
        {
            date: '',
            description: 'Angsuran BFKO',
            angsuran: 'Angsuran ke-24',
            category: 'BFKO',
            total: 'Rp10 M',
            status: 'In Progress'
        },
        {
            date: '12-08-2025',
            description: 'Angsuran BFKO',
            angsuran: 'Angsuran ke-24',
            category: 'BFKO',
            total: 'Rp10 M',
            status: 'Complete'
        }
    ];

    return (
        <MainLayout>
            <div className="p-8">
                {/* Search and Filters */}
                <div className="flex gap-4 mb-6">
                    <div className="flex-1 relative">
                        <input
                            type="text"
                            placeholder="Search"
                            className="w-full px-4 py-2 pl-10 bg-cyan-100 border-0 rounded-lg focus:ring-2 focus:ring-cyan-300"
                        />
                        <svg className="w-5 h-5 absolute left-3 top-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <button className="px-6 py-2 bg-cyan-300 text-gray-800 rounded-lg hover:bg-cyan-400 transition">
                        New Transaction
                    </button>
                    <button className="px-6 py-2 bg-cyan-200 text-gray-800 rounded-lg hover:bg-cyan-300 transition flex items-center gap-2">
                        Filter
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                {/* Person Name */}
                <h1 className="text-3xl font-bold mb-6">{person}</h1>

                {/* Charts Row */}
                <div className="bg-gray-200 rounded-lg p-6 mb-6">
                    <div className="grid grid-cols-2 gap-6">
                        {/* Progress Chart */}
                        <div className="flex items-center justify-center">
                            <div className="relative w-48 h-48">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={progressData}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={60}
                                            outerRadius={85}
                                            paddingAngle={0}
                                            dataKey="value"
                                            startAngle={90}
                                            endAngle={-270}
                                        >
                                            <Cell fill="#22c55e" />
                                            <Cell fill="#fbbf24" />
                                        </Pie>
                                    </PieChart>
                                </ResponsiveContainer>
                                <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                    <p className="text-4xl font-bold text-center">90%</p>
                                </div>
                            </div>
                            <div className="ml-8 space-y-3">
                                <div className="flex items-center">
                                    <div className="w-4 h-4 bg-green-500 rounded-full mr-3"></div>
                                    <span className="text-gray-700 font-medium">Complete</span>
                                </div>
                                <div className="flex items-center">
                                    <div className="w-4 h-4 bg-yellow-400 rounded-full mr-3"></div>
                                    <span className="text-gray-700 font-medium">In Progress</span>
                                </div>
                            </div>
                        </div>

                        {/* Monthly Payment Chart */}
                        <div>
                            <h3 className="text-lg font-bold mb-4">Monthly Payment</h3>
                            <ResponsiveContainer width="100%" height={200}>
                                <BarChart data={monthlyData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="month" />
                                    <YAxis 
                                        tickFormatter={(value) => `${value} M`}
                                        domain={[0, 6]}
                                    />
                                    <Bar dataKey="amount" fill="#22c55e" radius={[8, 8, 0, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </div>
                </div>

                {/* Payment Details Table */}
                <div className="bg-white rounded-lg shadow">
                    <div className="p-6">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-xl font-bold">Payment Details</h2>
                            <button className="text-blue-600 hover:text-blue-800 font-medium">view</button>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b">
                                        <th className="text-left py-3 px-4 font-semibold text-gray-700">Date</th>
                                        <th className="text-left py-3 px-4 font-semibold text-gray-700">Description</th>
                                        <th className="text-left py-3 px-4 font-semibold text-gray-700">Angsuran</th>
                                        <th className="text-left py-3 px-4 font-semibold text-gray-700">Category</th>
                                        <th className="text-left py-3 px-4 font-semibold text-gray-700">Total</th>
                                        <th className="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {payments.map((payment, index) => (
                                        <tr key={index} className="border-b hover:bg-gray-50">
                                            <td className="py-4 px-4">{payment.date || '-'}</td>
                                            <td className="py-4 px-4">{payment.description}</td>
                                            <td className="py-4 px-4">{payment.angsuran}</td>
                                            <td className="py-4 px-4">{payment.category}</td>
                                            <td className="py-4 px-4 font-semibold">{payment.total}</td>
                                            <td className="py-4 px-4">
                                                <span className={`font-medium ${
                                                    payment.status === 'Complete' 
                                                        ? 'text-green-600' 
                                                        : 'text-yellow-600'
                                                }`}>
                                                    {payment.status}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}
