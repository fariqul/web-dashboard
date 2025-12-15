import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';

export default function MainLayout({ children }) {
    const { url } = usePage();
    const [openDropdown, setOpenDropdown] = useState(null);
    
    const navigation = [
        { name: 'Dashboard Overview', href: '/', emoji: '🏠', description: 'Main dashboard' },
        { 
            name: 'Monitoring Dashboards', 
            emoji: '📊',
            isDropdown: true,
            description: 'Data monitoring systems',
            items: [
                { name: 'BFKO Monitoring', href: '/bfko', emoji: '⚡', description: 'Payroll deductions' },
                { name: 'CC Card Monitoring', href: '/cc-card', emoji: '💳', description: 'Corporate card usage' },
                { name: 'Service Fee Monitoring', href: '/service-fee', emoji: '🏨', description: 'Hotel & Flight bookings' },
                { name: 'SPPD Monitoring', href: '/sppd', emoji: '📋', description: 'Travel assignments' },
            ]
        },
        { name: 'Documents', href: '/documents', emoji: '📄', description: 'File management' },
    ];

    // Auto-expand dropdown if user is on a monitoring page
    useEffect(() => {
        const monitoringRoutes = ['/bfko', '/cc-card', '/service-fee', '/sppd'];
        const isOnMonitoringPage = monitoringRoutes.some(route => 
            url === route || url.startsWith(route + '/')
        );
        
        if (isOnMonitoringPage) {
            setOpenDropdown('monitoring');
        }
    }, [url]);

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
                        if (item.isDropdown) {
                            // Dropdown menu item
                            const isOpen = openDropdown === 'monitoring';
                            const isAnyChildActive = item.items.some(child => 
                                url === child.href || (child.href !== '/' && url.startsWith(child.href))
                            );
                            
                            return (
                                <div key={item.name}>
                                    {/* Dropdown Toggle */}
                                    <button
                                        onClick={() => setOpenDropdown(isOpen ? null : 'monitoring')}
                                        className={`group w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 ${
                                            isAnyChildActive
                                                ? 'bg-white/25 backdrop-blur-md text-white shadow-lg shadow-black/10 border border-white/30'
                                                : 'text-cyan-50/90 hover:bg-white/15 hover:text-white hover:scale-105'
                                        }`}
                                    >
                                        {/* Active Indicator */}
                                        {isAnyChildActive && (
                                            <div className="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-white rounded-r-full shadow-lg"></div>
                                        )}
                                        
                                        {/* Icon Container */}
                                        <div className={`flex items-center justify-center w-10 h-10 rounded-lg transition-all duration-300 ${
                                            isAnyChildActive 
                                                ? 'bg-white/20' 
                                                : 'bg-white/10 group-hover:bg-white/15'
                                        }`}>
                                            <span className="text-xl">{item.emoji}</span>
                                        </div>
                                        
                                        <div className="flex-1 text-left">
                                            <span className="font-semibold block">{item.name}</span>
                                            <span className="text-xs text-cyan-50/70">{item.description}</span>
                                        </div>
                                        
                                        {/* Chevron */}
                                        <svg 
                                            className={`w-5 h-5 transition-transform duration-300 ${isOpen ? 'rotate-180' : ''}`} 
                                            fill="currentColor" 
                                            viewBox="0 0 20 20"
                                        >
                                            <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                                        </svg>
                                    </button>
                                    
                                    {/* Dropdown Content */}
                                    <div className={`overflow-hidden transition-all duration-300 ${
                                        isOpen ? 'max-h-96 opacity-100 mt-2' : 'max-h-0 opacity-0'
                                    }`}>
                                        <div className="space-y-1 ml-3 pl-4 border-l-2 border-white/20">
                                            {item.items.map((child) => {
                                                const isChildActive = url === child.href || (child.href !== '/' && url.startsWith(child.href));
                                                return (
                                                    <Link
                                                        key={child.name}
                                                        href={child.href}
                                                        className={`group relative flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-300 ${
                                                            isChildActive
                                                                ? 'bg-white/20 text-white shadow-md scale-105'
                                                                : 'text-cyan-50/80 hover:bg-white/10 hover:text-white hover:scale-105'
                                                        }`}
                                                    >
                                                        <div className={`flex items-center justify-center w-8 h-8 rounded-lg transition-all duration-300 ${
                                                            isChildActive 
                                                                ? 'bg-white/25' 
                                                                : 'bg-white/5 group-hover:bg-white/10'
                                                        }`}>
                                                            <span className="text-base">{child.emoji}</span>
                                                        </div>
                                                        
                                                        <div className="flex-1">
                                                            <span className="text-sm font-semibold block">{child.name}</span>
                                                            <span className="text-xs text-cyan-50/60">{child.description}</span>
                                                        </div>
                                                        
                                                        {/* Active Dot */}
                                                        {isChildActive && (
                                                            <div className="w-2 h-2 bg-white rounded-full shadow-lg"></div>
                                                        )}
                                                    </Link>
                                                );
                                            })}
                                        </div>
                                    </div>
                                </div>
                            );
                        } else {
                            // Regular menu item
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
                                    
                                    <div className="flex-1">
                                        <span className="font-semibold block">{item.name}</span>
                                        <span className="text-xs text-cyan-50/70">{item.description}</span>
                                    </div>
                                    
                                    {/* Hover Arrow */}
                                    {!isActive && (
                                        <svg className="w-4 h-4 ml-auto opacity-0 group-hover:opacity-100 transition-opacity" fill="currentColor" viewBox="0 0 20 20">
                                            <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clipRule="evenodd" />
                                        </svg>
                                    )}
                                </Link>
                            );
                        }
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
 