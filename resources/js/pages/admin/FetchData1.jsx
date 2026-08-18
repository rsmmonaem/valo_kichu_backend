import React, { useState } from "react";
import api from "../../services/api";

function FetchData1() {  // Changed from FetchData1 to FetchData
  const [loading, setLoading] = useState(false);
  const [importStats, setImportStats] = useState({
    total: 0,
    created: 0,
    skipped: 0,
    failed: 0
  });
  const [processedCount, setProcessedCount] = useState(0);
  const [failedProducts, setFailedProducts] = useState([]);

  const handleFetchData = async () => {
    if (!window.confirm('Are you sure you want to import products from Mohasagor? This may take several minutes.')) {
      return;
    }

    setLoading(true);
    setImportStats({
      total: 0,
      created: 0,
      skipped: 0,
      failed: 0
    });
    setProcessedCount(0);
    setFailedProducts([]);

    try {
      console.log("Starting import...");
      const response = await api.post("/admin/v1/mohasagor/import");
      
      console.log("Import response:", response.data);
      
      if (response.data.success) {
        const { stats, data } = response.data;
        
        setImportStats(stats);
        setProcessedCount(stats.total);
        
        if (data.failed_products && data.failed_products.length > 0) {
          setFailedProducts(data.failed_products);
        }
        
        alert(`Import completed!\n\nTotal: ${stats.total}\nCreated: ${stats.created}\nSkipped: ${stats.skipped}\nFailed: ${stats.failed}\n\nCheck console for failed product details.`);
        
        // Log failed products to console
        if (data.failed_products && data.failed_products.length > 0) {
          console.warn("Failed products:", data.failed_products);
        }
      } else {
        alert(`Import failed: ${response.data.message || 'Unknown error'}`);
      }
      
    } catch (error) {
      console.error("Error importing products:", error);
      console.error("Error response:", error.response?.data);
      alert(`Error importing products: ${error.response?.data?.message || error.message || 'Please check the console for details.'}`);
    } finally {
      setLoading(false);
    }
  };

  const debugDatabase = async () => {
    try {
      const response = await api.get("/admin/v1/mohasagor/debug");
      console.log("Database debug info:", response.data);
      alert('Check console for database structure information.');
    } catch (error) {
      console.error("Debug failed:", error);
      alert(`Debug failed: ${error.message}`);
    }
  };

  return (
    <div className="flex flex-col justify-center items-center min-h-screen bg-gray-50 p-6">
      <div className="bg-white rounded-lg shadow-lg p-8 max-w-4xl w-full">
        <h1 className="text-3xl font-bold text-gray-800 mb-2 text-center">
          Import Products from Mohasagor
        </h1>
        <p className="text-gray-600 mb-6 text-center">
          Import products from Mohasagor API to your store
        </p>
        
        <div className="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h3 className="font-semibold text-blue-800 mb-2">How it works:</h3>
          <ul className="text-blue-700 text-sm list-disc pl-5 space-y-1">
            <li>Fetches products from Mohasagor API</li>
            <li>Checks for duplicate products and categories</li>
            <li>Creates categories if they don't exist</li>
            <li>Creates products without gallery images first</li>
            <li>Adds gallery images separately</li>
            <li>All processing happens on the server</li>
          </ul>
        </div>

        <div className="mb-6 flex space-x-4">
          <button
            className="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200"
            onClick={debugDatabase}
          >
            Debug Database
          </button>
          <button
            className="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 disabled:opacity-50"
            onClick={handleFetchData}
            disabled={loading}
          >
            {loading ? (
              <>
                <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Importing...
              </>
            ) : "Start Import"}
          </button>
        </div>

        {importStats.total > 0 && (
          <div className="mb-6">
            <div className="flex justify-between items-center mb-2">
              <span className="text-sm font-medium text-gray-700">Progress</span>
              <span className="text-sm text-gray-600">
                {processedCount} / {importStats.total}
              </span>
            </div>
            <div className="w-full bg-gray-200 rounded-full h-2.5">
              <div 
                className="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                style={{ width: `${(processedCount / importStats.total) * 100}%` }}
              ></div>
            </div>
            
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
              <div className="bg-green-50 border border-green-200 rounded-lg p-3 text-center">
                <div className="text-2xl font-bold text-green-700">{importStats.created}</div>
                <div className="text-sm text-green-600">Created</div>
              </div>
              <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-center">
                <div className="text-2xl font-bold text-yellow-700">{importStats.skipped}</div>
                <div className="text-sm text-yellow-600">Skipped</div>
              </div>
              <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-center">
                <div className="text-2xl font-bold text-red-700">{importStats.failed}</div>
                <div className="text-sm text-red-600">Failed</div>
              </div>
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 text-center">
                <div className="text-2xl font-bold text-blue-700">{importStats.total}</div>
                <div className="text-sm text-blue-600">Total</div>
              </div>
            </div>
          </div>
        )}

        {failedProducts.length > 0 && (
          <div className="mb-6">
            <h3 className="font-semibold text-red-700 mb-2">Failed Products ({failedProducts.length})</h3>
            <div className="bg-red-50 border border-red-200 rounded-lg p-4 max-h-60 overflow-y-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-red-200">
                    <th className="text-left py-2 text-red-700">Name</th>
                    <th className="text-left py-2 text-red-700">API ID</th>
                    <th className="text-left py-2 text-red-700">Category</th>
                    <th className="text-left py-2 text-red-700">Reason</th>
                  </tr>
                </thead>
                <tbody>
                  {failedProducts.slice(0, 10).map((product, index) => (
                    <tr key={index} className="border-b border-red-100">
                      <td className="py-2 text-red-600">{product.name}</td>
                      <td className="py-2 text-red-600">{product.api_id || 'N/A'}</td>
                      <td className="py-2 text-red-600">{product.category || 'N/A'}</td>
                      <td className="py-2 text-red-600">{product.reason}</td>
                    </tr>
                  ))}
                  {failedProducts.length > 10 && (
                    <tr>
                      <td colSpan="4" className="py-2 text-red-600 text-center">
                        ... and {failedProducts.length - 10} more
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        )}

        <div className="mt-6 text-sm text-gray-500 text-center">
          <p>This process may take several minutes depending on the number of products.</p>
          <p className="mt-1">Duplicates will be automatically skipped.</p>
          <p className="mt-1">Check your Laravel logs for detailed progress information.</p>
          <p className="mt-1">Check browser console for failed product details.</p>
        </div>
      </div>
    </div>
  );
}

export default FetchData1;  // Make sure this matches your component name