import React, { useEffect, useState } from "react";

const Carousel = ({ images }) => {
    const [selectedImage, setSelectedImage] = useState(images?.[0] || null);

    useEffect(() => {
        if (images?.length) {
            setSelectedImage(images[0]);
        }
    }, [images]);

    return (
        <div className="flex items-start gap-8">
            <div className="flex flex-col items-center gap-2 py-2">
                {images.map((image) => (
                    <button
                        onClick={() => setSelectedImage(image)}
                        className={`border-2 ${
                            selectedImage?.id === image.id
                                ? "border-blue-500"
                                : "hover:border-blue-500"
                        }`}
                        key={image.id}
                    >
                        <img src={image.thumb} alt="" className="w-[50px]" />
                    </button>
                ))}
            </div>
            {selectedImage && (
                <div className="carousel w-full">
                    <div className="carousel-item w-full">
                        <img src={selectedImage.large} className="w-full" alt="" />
                    </div>
                </div>
            )}
        </div>
    );
};

export default Carousel;
