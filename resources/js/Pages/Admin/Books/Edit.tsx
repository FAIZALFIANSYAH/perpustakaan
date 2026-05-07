import React, { FormEventHandler } from 'react';
import { router, useForm } from '@inertiajs/react';
import BookForm from '@/Components/Books/BookForm';
import AdminLayout from '@/Layouts/AdminLayout';

type Category = {
    id: number;
    name: string;
};

type Book = {
    id: number;
    category_id: number;
    title: string;
    author: string;
    publisher: string | null;
    isbn: string | null;
    publish_year: number | null;
    stock: number;
    cover: string | null;
    description: string | null;
    is_active: boolean;
};

type Props = {
    book: Book;
    categories: Category[];
};

export default function Edit({ book, categories }: Props) {
    const { data, setData, processing, errors } = useForm({
        category_id: book.category_id ? String(book.category_id) : '',
        title: book.title ?? '',
        author: book.author ?? '',
        publisher: book.publisher ?? '',
        isbn: book.isbn ?? '',
        publish_year: book.publish_year ? String(book.publish_year) : '',
        stock: String(book.stock ?? 0),
        cover: null,
        description: book.description ?? '',
        is_active: Boolean(book.is_active),
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        router.post(route('admin.books.update.post', book.id), data, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout>
            <BookForm
                title="Edit Book"
                description="Perbarui informasi buku yang sudah ada."
                categories={categories}
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                submit={submit}
                submitLabel="Update Book"
                initialCover={book.cover}
            />
        </AdminLayout>
    );
}
