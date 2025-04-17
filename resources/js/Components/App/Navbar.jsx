import { Link, useForm, usePage } from "@inertiajs/react";
import React, { useState } from "react";
import MiniCartDropdown from "./MiniCartDropdown";
import { MagnifyingGlassIcon } from "@heroicons/react/24/outline";

const Navbar = ({ selectedDepartment }) => {
    // Destructure data coming from the server-side Inertia props
    const { auth, totalQuantity, totalPrice, miniCartItems, departments, keyword } = usePage().props;
    const { user } = auth; // Extract authenticated user info

    // Initialize form state for the search bar using Inertia's useForm hook
    const searchForm = useForm({
        keyword: keyword || ''
    });
    // Get the current url
    const {url} = usePage();
    const onSubmit = (e) => {
        e.preventDefault(); // prevent default form submission behavior 

        // Send a Get request to the current URL with search data
        searchForm.get(url, {
            preserveScroll: true, // Keep current scroll position
            preserveState: true //
        });
    }
    return (
        <>
            <div className="navbar bg-base-100">
                <div className="flex-1">
                    <Link href="/" className="btn btn-ghost text-xl">
                        LuxeWear
                    </Link>
                </div>
                <div className="flex-none gap-4">
                    <form className="join flex-1" onSubmit={onSubmit}>
                        <div className="flex-1">
                            <input
                                type="text"
                                value={searchForm.data.keyword}
                                onChange={(e) =>
                                    searchForm.setData("keyword", e.target.value)
                                }
                                className="input input-bordered join-item w-full"
                                placeholder="Search"
                            />
                        </div>
                        <div className="indicator">
                            <button className="btn join-item">
                                <MagnifyingGlassIcon className="size-4" />
                                Search
                            </button>
                        </div>

                    </form>
                    <MiniCartDropdown
                        totalQuantity={totalQuantity}
                        totalPrice={totalPrice}
                        miniCartItems={miniCartItems}
                    />
                    {user && (
                        <div className="dropdown dropdown-end">
                            <div
                                tabIndex="0"
                                role="button"
                                className="btn btn-ghost btn-circle avatar"
                            >
                                <div className="w-10 rounded-full">
                                    <img
                                        alt="Tailwind CSS Navbar component"
                                        src="https://img.daisyui.com/images/stock/photo-1534528741775-53994a69daeb.webp"
                                    />
                                </div>
                            </div>
                            <ul
                                tabIndex="0"
                                className="menu menu-sm dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-52 p-2 shadow"
                            >
                                <li>
                                    <Link
                                        href={route("profile.edit")}
                                        className="justify-between"
                                    >
                                        Profile
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href={route("logout")}
                                        method="POST"
                                        as="button"
                                    >
                                        Logout
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    )}
                    {!user && (
                        <>
                            <Link href={route("login")} className="btn">
                                Login
                            </Link>
                            <Link
                                href={route("register")}
                                className="btn btn-primary"
                            >
                                Register
                            </Link>
                        </>
                    )}
                </div>
            </div>
            <div className="navbar bg-base-100 border-t min-h-4">
                <div className="navbar-center hidden lg:flex">
                    <ul className="menu menu-horizontal px-1 z-20 py-0">
                        {departments.map((department) => (
                            <li
                                key={department.id}
                                className={`
                                    rounded transition
                                    ${
                                        selectedDepartment?.id === department.id
                                            ? "bg-gray-300"
                                            : ""
                                    }
                                `}
                            >
                                <Link
                                    href={route(
                                        "product.byDepartment",
                                        department.slug
                                    )}
                                >
                                    {department.name}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </>
    );
};

export default Navbar;
