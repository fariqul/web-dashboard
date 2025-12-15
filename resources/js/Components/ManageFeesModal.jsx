import React, { useState, useEffect } from 'react';
import axios from 'axios';

export default function ManageFeesModal({ isOpen, onClose, selectedSheet }) {
    const [fees, setFees] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [editedFees, setEditedFees] = useState({});

    useEffect(() => {
        if (isOpen) {
            loadFees();
        }
    }, [isOpen]);

    const loadFees = async () => {
        setLoading(true);
        try {
            const response = await axios.get('/cc-card/fees');
            setFees(response.data);
            
            // Initialize editedFees
            const initial = {};
            response.data.forEach(fee => {
                initial[fee.sheet_name] = {
                    biaya_adm_bunga: fee.biaya_adm_bunga,
                    biaya_transfer: fee.biaya_transfer,
                    iuran_tahunan: fee.iuran_tahunan
                };
            });
            setEditedFees(initial);
        } catch (error) {
            console.error('Failed to load fees:', error);
            alert('Failed to load fees');
        } finally {
            setLoading(false);
        }
    };

    const handleInputChange = (sheetName, field, value) => {
        setEditedFees(prev => ({
            ...prev,
            [sheetName]: {
                ...(prev[sheetName] || {}),
                [field]: parseFloat(value) || 0
            }
        }));
    };

    const handleSave = async () => {
        setSaving(true);
        try {
            const feesArray = Object.keys(editedFees).map(sheetName => ({
                sheet_name: sheetName,
                ...editedFees[sheetName]
            }));

            await axios.post('/cc-card/fees/update', { fees: feesArray });
            alert('Fees updated successfully!');
            onClose();
            window.location.reload();
        } catch (error) {
            console.error('Failed to update fees:', error);
            alert('Failed to update fees: ' + (error.response?.data?.error || error.message));
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async (sheetName) => {
        if (!confirm(`Are you sure you want to delete additional fees for "${sheetName}"?`)) {
            return;
        }

        try {
            await axios.delete('/cc-card/fees/delete', {
                data: { sheet_name: sheetName }
            });
            alert('Fees deleted successfully!');
            loadFees(); // Reload to refresh the list
            window.location.reload();
        } catch (error) {
            console.error('Failed to delete fees:', error);
            alert('Failed to delete fees: ' + (error.response?.data?.error || error.message));
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                {/* Header */}
                <div className="flex justify-between items-center p-6 border-b">
                    <h2 className="text-2xl font-bold">Manage Additional Fees</h2>
                    <button onClick={onClose} className="text-gray-500 hover:text-gray-700">
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {/* Content */}
                <div className="p-6 overflow-y-auto flex-1">
                    {loading ? (
                        <div className="text-center py-8">Loading...</div>
                    ) : fees.length === 0 ? (
                        <div className="text-center py-8 text-gray-500">No fees configured yet</div>
                    ) : (
                        <div className="space-y-6">
                            {fees.map(fee => (
                                <div key={fee.sheet_name} className="border rounded-lg p-4 hover:shadow-md transition">
                                    <div className="flex justify-between items-center mb-4">
                                        <h3 className="text-lg font-semibold">{fee.sheet_name}</h3>
                                        <button
                                            onClick={() => handleDelete(fee.sheet_name)}
                                            className="px-3 py-1 text-sm bg-red-500 text-white rounded hover:bg-red-600 transition"
                                            title="Delete fees for this sheet"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                    <div className="grid grid-cols-3 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Biaya ADM & Bunga
                                            </label>
                                            <input
                                                type="number"
                                                value={editedFees[fee.sheet_name]?.biaya_adm_bunga || 0}
                                                onChange={(e) => handleInputChange(fee.sheet_name, 'biaya_adm_bunga', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                                min="0"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Biaya Transfer
                                            </label>
                                            <input
                                                type="number"
                                                value={editedFees[fee.sheet_name]?.biaya_transfer || 0}
                                                onChange={(e) => handleInputChange(fee.sheet_name, 'biaya_transfer', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                                min="0"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Iuran Tahunan
                                            </label>
                                            <input
                                                type="number"
                                                value={editedFees[fee.sheet_name]?.iuran_tahunan || 0}
                                                onChange={(e) => handleInputChange(fee.sheet_name, 'iuran_tahunan', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"
                                                min="0"
                                            />
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Footer */}
                <div className="flex justify-end gap-3 p-6 border-t bg-gray-50">
                    <button
                        onClick={onClose}
                        className="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={handleSave}
                        disabled={saving}
                        className="px-6 py-2 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 transition disabled:opacity-50"
                    >
                        {saving ? 'Saving...' : 'Save Changes'}
                    </button>
                </div>
            </div>
        </div>
    );
}
