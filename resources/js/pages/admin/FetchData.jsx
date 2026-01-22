import React, { useState } from 'react';
import api from "../../services/api";
import axios from 'axios';

function FetchData() {
    const [loading, setLoading] = useState(false);

    const handleFetchData = async () => {
        console.log("hello");
        setLoading(true);

        try {
            const { data } = await api.get("/admin/v1/categories");
            console.log("Categories:", data);

            const mohasagor = await axios.get(
                "https://mohasagor.com.bd/api/reseller/product",
                {
                    headers: {
                        "api-key": "a54cc12a353bf771ec9ab6770e71ca5451895215380dc2de15e94a2b8b08b5f4",
                        "secret-key": "DAT4HzshwLvUtToO",
                        "Accept": "application/json",
                    }
                }
            );
            
            console.log(mohasagor.data);

        } catch (error) {
            console.error("Fetch error:", error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="flex flex-col justify-center items-center">
            <h1>Fetch data from Mohasagor API</h1>

            <button
                className="mt-20 bg-primary p-3 rounded-md text-white cursor-pointer"
                onClick={handleFetchData}
                disabled={loading}
            >
                {loading ? "Loading..." : "Fetch Data"}
            </button>
        </div>
    );
}

export default FetchData;
