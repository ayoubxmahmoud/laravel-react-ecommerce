import Navbar from '@/Components/App/Navbar';
import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

export default function AuthenticatedLayout({ header, children, selectedDepartment }) {
    const props = usePage().props;
    const user = props.auth.user;
    const [successMessages, setSuccessMessages] = useState([]);
    const timeoutRefs = useRef({});
        
    useEffect(() => {
        if (props.success.message) {
            const newMessage = {
                ...props.success,
                id: props.success.time, // Use time as unique identifier
            };

            // Add the new message to the list
            setSuccessMessages((prevMessages) => [newMessage, ...prevMessages]);

            // Set timeout for the specific message so it disappears after 5 seconds
            const timeoutId = setTimeout(() => {
                // Remove the message with the matching ID from the success messages state
                setSuccessMessages((prevMessages) => 
                    prevMessages.filter((msg) => msg.id !== newMessage.id)
                );
                // Clean up the timeout reference of the new message from the timeoutRefs object
                delete timeoutRefs.current[newMessage.id];
            }, 5000);

            // Store the timeout ID in the Ref
            timeoutRefs.current[newMessage.id] = timeoutId;
        }
    }, [props.success]);
    return (
        <div className="min-h-screen bg-gray-100 dark:bg-gray-900">
            <Navbar selectedDepartment={selectedDepartment} />

            {props.error && (
                <div className="container mx-auto px-8 mt-8">
                    <div className="alert alert-error">
                        {props.error}
                    </div>
                </div>
            )}

            {successMessages.length > 0 && (
                <div className="toast toast-top toast-end z-[1000] mt-16">
                    {successMessages.map((msg) => (
                        <div className="alert alert-success" key={msg.id}>
                            <span>{msg.message}</span>
                        </div>
                    ))}
                </div>
            )}

            <main>{children}</main>
        </div>
    );
}
