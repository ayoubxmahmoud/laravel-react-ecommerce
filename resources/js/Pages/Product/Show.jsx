import Carousel from "@/Components/core/Carousel";
import CurrencyFormatter from "@/Components/core/CurrencyFormatter";
import { arraysAreEqual } from "@/helper";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout"; // Layout wrapper for authenticated users
import { Head, Link, router, useForm, usePage } from "@inertiajs/react";
import { useEffect, useMemo, useState } from "react";

const Show = ({ product, variationOptions }) => {
    const appName = usePage().props.appName;
    
    // Initialize form state with default values
    const form = useForm({
        option_ids: {},
        quantity: 1,
        price: null,
    });

    const { url } = usePage();
    // State to store the selected product variations
    const [selectedOptions, setSelectedOptions] = useState([]);

    // Computed images to display based on the selected options
    const images = useMemo(() => {
        for (let typeId in selectedOptions) {
            const option = selectedOptions[typeId];
            if (option?.images?.length > 0) return option.images;
        }
        return product.images;
    }, [product, selectedOptions]);
    // Compute price and quantity based on the selected options
    const computedProduct = useMemo(() => {
        const selectedOptionIds = Object.values(selectedOptions)
            .map((op) => op.id)
            .sort();
        for (let variation of product.variations) {
            const optionIds = variation.variation_type_option_ids.sort();
            if (arraysAreEqual(selectedOptionIds, optionIds)) {
                return {
                    price: variation.price,
                    quantity:
                        variation.quantity === null
                            ? Number.MAX_VALUE // Unlimited stock
                            : variation.quantity,
                };
            }
        }
        return {
            price: product.price,
            quantity: product.quantity,
        };
    }, [product, selectedOptions]);

    useEffect(() => {
        for (let type of product.variationTypes) {
            const selectedOptionId = variationOptions[type.id];

            chooseOption(
                type.id,
                type.options.find((op) => op.id === Number(selectedOptionId)) ||
                    type.options[0],
                false // Avoid updating router on initial load
            );
        }
    }, []);
    // Helper function to map selected options to option ids
    const getOptionIdsMap = (newOptions) => {
        return Object.fromEntries(
            Object.entries(newOptions).map(([a, b]) => [a, b.id])
        );
    };
    // function to handle option selection
    const chooseOption = (typeId, option, updatedRouter = true) => {
        setSelectedOptions((prevSelectedOptions) => {
            // Create a new state object by spreading the previous selected options
            // Add new option based on the given typeId and option
            const newOptions = {
                ...prevSelectedOptions,
                [typeId]: option,
            };
            if (updatedRouter) {
                router.get(
                    url,
                    { options: getOptionIdsMap(newOptions) }, // Convert selected options to an ID map
                    {
                        preserveScroll: true, // Prevents the page from scrolling to the top on navigation
                        preserveState: true, // Maintains the existing state in the app
                    }
                );
            }
            return newOptions;
        });
    };
    // Handle quantity change
    const onQuantityChange = (ev) => {
        form.setData("quantity", parseInt(ev.target.value));
    };

    // Function to add product to cart
    const addToCart = () => {
        console.log(product);
        form.post(route("cart.store", product.id), {
            preserveScroll: true,
            preserveState: true,
            onError: (err) => {
                console.log(err);
            },
        });
    };
    // Render product variation options (Image/Radio types)
    const renderProductVariationTypes = () => {
        return product.variationTypes.map((type, i) => (
            <div key={type.id}>
                <b className="pr-4">{type.name}</b>
                {type.type === "Image" && (
                    <div className="flex gap-2 mb-4">
                        {type.options.map((option) => {
                            return (
                                <div
                                    onClick={() =>
                                        chooseOption(type.id, option)
                                    }
                                    key={option.id}
                                >
                                    {option.images && (
                                        <img
                                            src={option.images[0].thumb}
                                            alt=""
                                            className={
                                                "w-[50px] " +
                                                (selectedOptions[type.id]
                                                    ?.id === option.id
                                                    ? "outline outline-4 outline-primary"
                                                    : "")
                                            }
                                        />
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}
                {type.type === "Radio" && (
                    <div className="flex join mb-4">
                        {type.options.map((option) => (
                            <input
                                type="radio"
                                onChange={() => chooseOption(type.id, option)}
                                key={option.id}
                                className="join-item btn"
                                value={option.id}
                                name={"variation_type_" + type.id}
                                checked={
                                    selectedOptions[type.id]?.id === option.id
                                }
                                aria-label={option.name}
                            />
                        ))}
                    </div>
                )}
            </div>
        ));
    };
    // Render quantity selector and add-to-cart button
    const renderAddToCartButton = () => {
        return (
            <div className="flex mb-8 gap-4">
                <select
                    value={form.quantity}
                    onChange={onQuantityChange}
                    className="select select-bordered w-full"
                >
                    {Array.from({
                        length: Math.min(10, computedProduct.quantity),
                    }).map((el, i) => (
                        <option value={i + 1} key={i + 1}>
                            Quantity: {i + 1}
                        </option>
                    ))}
                </select>
                <button onClick={addToCart} className="btn btn-primary">
                    Add to Cart
                </button>
            </div>
        );
    };
    // Update form when selected options change
    useEffect(() => {
        const idsMap = Object.fromEntries(
            Object.entries(selectedOptions).map(([typeId, option]) => [
                typeId,
                option.id,
            ])
        );
        form.setData("option_ids", idsMap);
    }, [selectedOptions]);

    return (
        <AuthenticatedLayout>
            <Head>
                <title>{product.title}</title>
                <meta name="title" content={product.meta_title || product.title} />
                <meta
                    name="description"
                    content={product.meta_description}
                />
                <link
                    rel="canonical"
                    href={route("product.show", product.slug)}
                />

                <meta property="og:title" content={product.title} />
                <meta
                    property="og:description"
                    content={product.meta_description}
                />
                <meta property="og:image" content={images[0]?.small} />
                <meta
                    property="og:url"
                    content={route("product.show", product.slug)}
                />
                <meta property="og:type" content="product" />
                <meta property="og:site_name" content={appName} />
            </Head>{" "}
            <div className="container mx-auto p-8">
                <div className="grid gap-8 grid-cols-1 lg:grid-cols-12">
                    <div className="col-span-7">
                        <Carousel images={images} />
                    </div>
                    <div className="col-span-5">
                        <h1 className="text-2xl">{product.title}</h1>
                        <p className="mb-8">
                            by{" "}
                            <Link
                                href={route(
                                    "vendor.profile",
                                    product.user.store_name
                                )}
                                className="hover:underline"
                            >
                                {product.user.store_name}
                            </Link>
                            &nbsp; in{" "}
                            <Link
                                href={route(
                                    "product.byDepartment",
                                    product.department.slug
                                )}
                                className="hover:underline"
                            >
                                {product.department.name}
                            </Link>
                        </p>
                        <div>
                            <div className="text-3xl font-semibold">
                                <CurrencyFormatter
                                    amount={computedProduct.price}
                                />
                            </div>
                        </div>
                        {renderProductVariationTypes()}

                        {computedProduct.quantity != undefined &&
                            computedProduct.quantity < 10 && (
                                <div className="text-error my-4">
                                    <span>
                                        Only {computedProduct.quantity} left
                                    </span>
                                </div>
                            )}

                        {renderAddToCartButton()}

                        <b className="text-xl">About the Item</b>
                        <div
                            className="wysiwyg-output"
                            dangerouslySetInnerHTML={{
                                __html: product.description,
                            }}
                        ></div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default Show;
