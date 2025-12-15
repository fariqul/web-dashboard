import React, { useState, useMemo } from 'react';
import { router } from '@inertiajs/react';

export default function DeleteSheetModal({ isOpen, onClose, sheets }) {
    const [selectedSheet, setSelectedSheet] = useState('');
    const [serviceType, setServiceType] = useState('all');
    const [isDeleting, setIsDeleting] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const [expandedYears, setExpandedYears] = useState(new Set());

    // Toggle year expansion
    const toggleYear = (year) => {
        setExpandedYears(prev => {
            const newSet = new Set(prev);
            if (newSet.has(year)) {
                newSet.delete(year);
            } else {
                newSet.add(year);
            }
            return newSet;
        });
    };

    // Group sheets by year
    const groupedSheets = useMemo(() => {
        if (!sheets || sheets.length === 0) return {};
        
        // Month name mapping for sorting (Indonesian and English)
        const monthOrder = {
            'januari': 1, 'january': 1, 'jan': 1,
            'februari': 2, 'february': 2, 'feb': 2,
            'maret': 3, 'march': 3, 'mar': 3,
            'april': 4, 'apr': 4,
            'mei': 5, 'may': 5,
            'juni': 6, 'june': 6, 'jun': 6,
            'juli': 7, 'july': 7, 'jul': 7,
            'agustus': 8, 'august': 8, 'agt': 8, 'aug': 8,
            'september': 9, 'sept': 9, 'sep': 9,
            'oktober': 10, 'october': 10, 'okt': 10, 'oct': 10,
            'november': 11, 'nov': 11,
            'desember': 12, 'december': 12, 'des': 12, 'dec': 12
        };
        
        const getMonthNumber = (sheetName) => {
            const lower = sheetName.toLowerCase();
            for (const [monthName, order] of Object.entries(monthOrder)) {
                if (lower.includes(monthName)) {
                    return order;
                }
            }
            return 99; // Unknown months go to the end
        };
        
        const groups = {};
        sheets.forEach(sheet => {
            // Extract year from sheet name (assuming format like "Juli 2025", "Agustus 2025", etc)
            const yearMatch = sheet.match(/\d{4}/);
            const year = yearMatch ? yearMatch[0] : 'Other';
            
            if (!groups[year]) {
                groups[year] = [];
            }
            groups[year].push(sheet);
        });
        
        // Sort years in descending order (newest first) and months in chronological order
        const sortedGroups = {};
        Object.keys(groups)
            .sort((a, b) => b.localeCompare(a))
            .forEach(year => {
                sortedGroups[year] = groups[year].sort((a, b) => {
                    const monthA = getMonthNumber(a);
                    const monthB = getMonthNumber(b);
                    return monthA - monthB;
                });
            });
        
        return sortedGroups;
    }, [sheets]);

    // Filter sheets based on search query
    const filteredGroupedSheets = useMemo(() => {
        if (!searchQuery) return groupedSheets;
        
        const filtered = {};
        Object.entries(groupedSheets).forEach(([year, sheetList]) => {
            const matchingSheets = sheetList.filter(sheet => 
                sheet.toLowerCase().includes(searchQuery.toLowerCase())
            );
            if (matchingSheets.length > 0) {
                filtered[year] = matchingSheets;
            }
        });
        return filtered;
    }, [groupedSheets, searchQuery]);

    // Auto-expand years when searching
    React.useEffect(() => {
        if (searchQuery) {
            const yearsWithResults = Object.keys(filteredGroupedSheets);
            setExpandedYears(new Set(yearsWithResults));
        }
    }, [searchQuery, filteredGroupedSheets]);

    if (!isOpen) return null;

    const handleDelete = (e) => {
        e.preventDefault();
        
        if (!selectedSheet) {
            alert('Please select a sheet to delete');
            return;
        }

        const typeLabel = serviceType === 'all' ? 'ALL data (Hotel + Flight)' : 
                         serviceType === 'hotel' ? 'Hotel data only' : 'Flight data only';
        
        const confirmMessage = `Are you sure you want to delete ${typeLabel} from sheet "${selectedSheet}"?\n\nThis action cannot be undone!`;
        
        if (!confirm(confirmMessage)) {
            return;
        }

        setIsDeleting(true);

        router.delete('/service-fee/sheet/delete', {
            data: {
                sheet: selectedSheet,
                service_type: serviceType
            },
            onSuccess: () => {
                setSelectedSheet('');
                setServiceType('all');
                onClose();
            },
            onError: (errors) => {
                console.error('Delete failed:', errors);
                alert('Failed to delete sheet data. Please try again.');
            },
            onFinish: () => {
                setIsDeleting(false);
            }
        });
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onClick={onClose}></div>

                <div className="relative bg-white rounded-lg shadow-xl max-w-md w-full">
                    {/* Icon Warning */}
                    <div className="flex items-center justify-center pt-6">
                        <div className="bg-red-100 rounded-full p-3">
                            <svg className="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                    </div>

                    {/* Content */}
                    <div className="p-6">
                        <h3 className="text-xl font-bold text-gray-900 mb-2 text-center">
                            Delete Sheet Data
                        </h3>
                        <p className="text-sm text-gray-600 mb-6 text-center">
                            This will permanently delete the selected data
                        </p>

                        <form onSubmit={handleDelete} className="space-y-4">
                            {/* Sheet Selection with Search and Grouping */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Select Sheet/Month *
                                </label>
                                
                                {/* Custom Dropdown */}
                                <div className="relative">
                                    {/* Selected Value / Trigger Button */}
                                    <button
                                        type="button"
                                        onClick={() => setIsDropdownOpen(!isDropdownOpen)}
                                        className="w-full px-3 py-2 text-left border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 bg-white flex items-center justify-between"
                                    >
                                        <span className={selectedSheet ? 'text-gray-900' : 'text-gray-400'}>
                                            {selectedSheet || '-- Choose Sheet --'}
                                        </span>
                                        <svg className={`w-5 h-5 transition-transform ${isDropdownOpen ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    {/* Dropdown Content */}
                                    {isDropdownOpen && (
                                        <div className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-80 overflow-hidden">
                                            {/* Search Box */}
                                            <div className="p-2 border-b sticky top-0 bg-white">
                                                <div className="relative">
                                                    <input
                                                        type="text"
                                                        placeholder="Search sheet..."
                                                        value={searchQuery}
                                                        onChange={(e) => setSearchQuery(e.target.value)}
                                                        className="w-full pl-8 pr-3 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-red-500"
                                                        onClick={(e) => e.stopPropagation()}
                                                    />
                                                    <svg className="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                    </svg>
                                                </div>
                                            </div>

                                            {/* Grouped Options */}
                                            <div className="overflow-y-auto max-h-64">
                                                {Object.keys(filteredGroupedSheets).length === 0 ? (
                                                    <div className="px-3 py-4 text-sm text-gray-500 text-center">
                                                        No sheets found
                                                    </div>
                                                ) : (
                                                    Object.entries(filteredGroupedSheets).map(([year, sheetList]) => {
                                                        const isExpanded = expandedYears.has(year);
                                                        
                                                        return (
                                                            <div key={year} className="border-b border-gray-100 last:border-0">
                                                                {/* Year Header - Clickable */}
                                                                <button
                                                                    type="button"
                                                                    onClick={() => toggleYear(year)}
                                                                    className="w-full px-3 py-2 bg-gradient-to-r from-red-50 to-orange-50 hover:from-red-100 hover:to-orange-100 text-xs font-bold text-red-800 uppercase tracking-wider sticky top-0 flex items-center gap-2 transition-colors"
                                                                >
                                                                    {/* Expand/Collapse Icon */}
                                                                    <svg 
                                                                        className={`w-3 h-3 transition-transform ${isExpanded ? 'rotate-90' : ''}`}
                                                                        fill="none" 
                                                                        stroke="currentColor" 
                                                                        viewBox="0 0 24 24"
                                                                    >
                                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                                                    </svg>
                                                                    
                                                                    <span>📅 {year}</span>
                                                                    
                                                                    <span className="ml-auto bg-red-600 text-white text-xs px-2 py-0.5 rounded-full font-semibold">
                                                                        {sheetList.length}
                                                                    </span>
                                                                </button>
                                                                
                                                                {/* Collapsible Sheets */}
                                                                {isExpanded && (
                                                                    <div className="bg-white">
                                                                        {sheetList.map(sheet => (
                                                                            <button
                                                                                key={sheet}
                                                                                type="button"
                                                                                onClick={() => {
                                                                                    setSelectedSheet(sheet);
                                                                                    setIsDropdownOpen(false);
                                                                                    setSearchQuery('');
                                                                                }}
                                                                                className={`w-full text-left px-4 py-2 text-sm hover:bg-red-50 transition border-l-2 ${
                                                                                    selectedSheet === sheet 
                                                                                        ? 'bg-red-100 text-red-900 font-medium border-red-600' 
                                                                                        : 'text-gray-700 border-transparent hover:border-red-300'
                                                                                }`}
                                                                            >
                                                                                <span className="pl-6">{sheet}</span>
                                                                                {selectedSheet === sheet && (
                                                                                    <span className="float-right text-red-600 font-bold">✓</span>
                                                                                )}
                                                                            </button>
                                                                        ))}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        );
                                                    })
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Service Type Selection */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    What to Delete *
                                </label>
                                <div className="space-y-2">
                                    <label className="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                        <input
                                            type="radio"
                                            name="service_type"
                                            value="all"
                                            checked={serviceType === 'all'}
                                            onChange={(e) => setServiceType(e.target.value)}
                                            className="w-4 h-4 text-red-600"
                                        />
                                        <div className="ml-3">
                                            <div className="font-medium text-gray-900">🗑️ All Data (Hotel + Flight)</div>
                                            <div className="text-xs text-gray-600">Delete everything in this sheet</div>
                                        </div>
                                    </label>

                                    <label className="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                        <input
                                            type="radio"
                                            name="service_type"
                                            value="hotel"
                                            checked={serviceType === 'hotel'}
                                            onChange={(e) => setServiceType(e.target.value)}
                                            className="w-4 h-4 text-red-600"
                                        />
                                        <div className="ml-3">
                                            <div className="font-medium text-gray-900">🏨 Hotel Data Only</div>
                                            <div className="text-xs text-gray-600">Keep flight data</div>
                                        </div>
                                    </label>

                                    <label className="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                        <input
                                            type="radio"
                                            name="service_type"
                                            value="flight"
                                            checked={serviceType === 'flight'}
                                            onChange={(e) => setServiceType(e.target.value)}
                                            className="w-4 h-4 text-red-600"
                                        />
                                        <div className="ml-3">
                                            <div className="font-medium text-gray-900">✈️ Flight Data Only</div>
                                            <div className="text-xs text-gray-600">Keep hotel data</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {/* Warning */}
                            {selectedSheet && (
                                <div className="bg-red-50 border border-red-200 rounded-lg p-3">
                                    <div className="flex items-start">
                                        <svg className="w-5 h-5 text-red-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                        </svg>
                                        <div className="text-sm text-red-800">
                                            <strong>Warning:</strong> You will delete{' '}
                                            <strong className="font-bold">
                                                {serviceType === 'all' ? 'ALL data' : 
                                                 serviceType === 'hotel' ? 'Hotel data' : 'Flight data'}
                                            </strong>
                                            {' '}from sheet <strong>"{selectedSheet}"</strong>.
                                            This action cannot be undone!
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Buttons */}
                            <div className="flex gap-3 pt-4">
                                <button
                                    type="button"
                                    onClick={onClose}
                                    className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
                                    disabled={isDeleting}
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50"
                                    disabled={isDeleting || !selectedSheet}
                                >
                                    {isDeleting ? 'Deleting...' : '🗑️ Delete'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}
