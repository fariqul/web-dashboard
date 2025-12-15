import React from 'react';
import { router } from '@inertiajs/react';

export default function Pagination({ data, preserveState = true, onPageChange }) {
    if (!data || !data.last_page || data.last_page <= 1) {
        return null;
    }

    const handlePageChange = (page) => {
        if (onPageChange) {
            onPageChange(page);
        }
    };

    const renderPageNumbers = () => {
        const pages = [];
        const currentPage = data.current_page;
        const lastPage = data.last_page;
        
        // Always show first page
        pages.push(1);
        
        // Show pages around current page
        for (let i = Math.max(2, currentPage - 1); i <= Math.min(lastPage - 1, currentPage + 1); i++) {
            if (!pages.includes(i)) {
                pages.push(i);
            }
        }
        
        // Always show last page
        if (!pages.includes(lastPage)) {
            pages.push(lastPage);
        }
        
        return pages;
    };

    const pageNumbers = renderPageNumbers();

    return (
        <div className="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4 px-4 py-3 bg-white border-t border-gray-200">
            {/* Info */}
            <div className="text-sm text-gray-700">
                Showing <span className="font-medium">{data.from || 0}</span> to{' '}
                <span className="font-medium">{data.to || 0}</span> of{' '}
                <span className="font-medium">{data.total || 0}</span> results
            </div>

            {/* Pagination Controls */}
            <div className="flex items-center gap-2">
                {/* Previous Button */}
                <button
                    onClick={() => handlePageChange(data.current_page - 1)}
                    disabled={data.current_page === 1}
                    className={`px-3 py-1 rounded-md text-sm font-medium transition ${
                        data.current_page === 1
                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                    }`}
                >
                    ← Prev
                </button>

                {/* Page Numbers */}
                {pageNumbers.map((page, index) => {
                    // Show ellipsis if there's a gap
                    if (index > 0 && page - pageNumbers[index - 1] > 1) {
                        return (
                            <React.Fragment key={`ellipsis-${page}`}>
                                <span className="px-2 text-gray-500">...</span>
                                <button
                                    onClick={() => handlePageChange(page)}
                                    className={`px-3 py-1 rounded-md text-sm font-medium transition ${
                                        page === data.current_page
                                            ? 'bg-cyan-500 text-white'
                                            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                    }`}
                                >
                                    {page}
                                </button>
                            </React.Fragment>
                        );
                    }

                    return (
                        <button
                            key={page}
                            onClick={() => handlePageChange(page)}
                            className={`px-3 py-1 rounded-md text-sm font-medium transition ${
                                page === data.current_page
                                    ? 'bg-cyan-500 text-white'
                                    : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                            }`}
                        >
                            {page}
                        </button>
                    );
                })}

                {/* Next Button */}
                <button
                    onClick={() => handlePageChange(data.current_page + 1)}
                    disabled={data.current_page === data.last_page}
                    className={`px-3 py-1 rounded-md text-sm font-medium transition ${
                        data.current_page === data.last_page
                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                    }`}
                >
                    Next →
                </button>
            </div>
        </div>
    );
}
