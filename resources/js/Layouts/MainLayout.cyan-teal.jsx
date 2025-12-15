import React from 'react';
import { Link, usePage } from '@inertiajs/react';

export default function MainLayout({ children }) {
    const { url } = usePage();
    
    const navigation = [
        { name: 'Dashboard', href: '/', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', emoji: '🏠' },
        { name: 'Transaction', href: '/transaction', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', emoji: '💳' },
        { name: 'Documents', href: '/documents', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', emoji: '📄' },
    ];

    return (
        <div className="flex h-screen bg-gradient-to-br from-gray-50 to-cyan-50/30">
            {/* Sidebar */}
            <div className="w-64 bg-gradient-to-br from-cyan-500 via-teal-600 to-cyan-700 text-white flex flex-col shadow-2xl relative overflow-hidden">
                {/* Decorative Background Elements */}
                <div className="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -mr-32 -mt-32"></div>
                <div className="absolute bottom-0 left-0 w-48 h-48 bg-teal-300/10 rounded-full blur-3xl -ml-24 -mb-24"></div>
                
                {/* Logo */}
                <div className="relative p-6 border-b border-white/20 backdrop-blur-sm">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-white/25 backdrop-blur-md rounded-xl flex items-center justify-center shadow-lg border border-white/30">
                            <span className="text-2xl">⚡</span>
                        </div>
                        <h1 className="text-xl font-bold text-white drop-shadow-lg">PLN Dashboard</h1>
                    </div>
                </div>

                {/* User Info */}
                <div className="relative p-6 border-b border-white/20 backdrop-blur-sm">
                    <div className="flex items-center gap-4">
                        <div className="relative">
                            <div className="w-12 h-12 bg-white/25 backdrop-blur-md rounded-full flex items-center justify-center shadow-lg ring-2 ring-white/30">
                                <svg className="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="absolute -bottom-1 -right-1 w-4 h-4 bg-green-400 rounded-full border-2 border-teal-600 shadow-lg"></div>
                        </div>
                        <div className="flex-1">
                            <p className="text-sm font-semibold text-white drop-shadow">User</p>
                            <p className="text-xs text-cyan-50/90">User@gmail.com</p>
                        </div>
                    </div>
                </div>

                {/* Navigation */}
                <nav className="relative flex-1 p-4 space-y-2 overflow-y-auto">
                    {navigation.map((item) => {
                        const isActive = url === item.href || (item.href !== '/' && url.startsWith(item.href));
                        return (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={`group relative flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 ${
                                    isActive
                                        ? 'bg-white/25 backdrop-blur-md text-white shadow-lg shadow-black/10 scale-105 border border-white/30'
                                        : 'text-cyan-50/90 hover:bg-white/15 hover:text-white hover:scale-105'
                                }`}
                            >
                                {/* Active Indicator */}
                                {isActive && (
                                    <div className="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-white rounded-r-full shadow-lg"></div>
                                )}
                                
                                {/* Icon Container */}
                                <div className={`flex items-center justify-center w-10 h-10 rounded-lg transition-all duration-300 ${
                                    isActive 
                                        ? 'bg-white/20' 
                                        : 'bg-white/10 group-hover:bg-white/15'
                                }`}>
                                    <span className="text-xl">{item.emoji}</span>
                                </div>
                                
                                <span className="font-semibold">{item.name}</span>
                                
                                {/* Hover Arrow */}
                                {!isActive && (
                                    <svg className="w-4 h-4 ml-auto opacity-0 group-hover:opacity-100 transition-opacity" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clipRule="evenodd" />
                                    </svg>
                                )}
                            </Link>
                        );
                    })}
                </nav>

                {/* Bottom Navigation */}
                <div className="relative p-4 space-y-2 border-t border-white/20 backdrop-blur-sm">
                    <Link
                        href="/settings"
                        className="group flex items-center gap-3 px-4 py-3 rounded-xl text-cyan-50/90 hover:bg-white/15 hover:text-white transition-all duration-300 hover:scale-105"
                    >
                        <div className="flex items-center justify-center w-10 h-10 rounded-lg bg-white/10 group-hover:bg-white/15 transition-all">
                            <span className="text-xl">⚙️</span>
                        </div>
                        <span className="font-semibold">Settings</span>
                    </Link>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="group flex items-center gap-3 px-4 py-3 rounded-xl text-cyan-50/90 hover:bg-gradient-to-r hover:from-red-500 hover:to-rose-500 hover:text-white transition-all duration-300 hover:scale-105 w-full hover:shadow-lg hover:shadow-red-500/30"
                    >
                        <div className="flex items-center justify-center w-10 h-10 rounded-lg bg-white/10 group-hover:bg-white/20 transition-all">
                            <span className="text-xl">🚪</span>
                        </div>
                        <span className="font-semibold">Log Out</span>
                    </Link>
                </div>
            </div>

            {/* Main Content */}
            <div className="flex-1 overflow-auto">
                {children}
            </div>
        </div>
    );
}
