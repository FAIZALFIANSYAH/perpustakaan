import React, { FormEventHandler } from 'react';
import { router, useForm } from '@inertiajs/react';
import BookForm from '@/Components/Books/BookForm';
import LibrarianLayout from '@/Layouts/LibrarianLayout';
import { useTranslation } from 'react-i18next';

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
    const { t } = useTranslation();
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

        router.post(route('librarian.books.update.post', book.id), data, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <LibrarianLayout>
            <BookForm
                title={t('edit_book')}
                description={t('edit_book_description')}
                categories={categories}
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                submit={submit}
                submitLabel={t('update_book')}
                backRoute="librarian.books.index"
                initialCover={book.cover}
            />
        </LibrarianLayout>
    );
}
