import React, { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import BorrowingForm from '@/Components/Borrowings/BorrowingForm';
import LibrarianLayout from '@/Layouts/LibrarianLayout';

type Member = {
    id: number;
    name: string;
    email: string;
};

type Book = {
    id: number;
    title: string;
    author: string;
    stock: number;
    category?: {
        name: string;
    } | null;
};

type Props = {
    members: Member[];
    books: Book[];
};

export default function Create({ members, books }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        member_id: '',
        borrowed_at: '',
        due_at: '',
        notes: '',
        items: [
            {
                book_id: '',
                quantity: 1,
                notes: '',
            },
        ],
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('librarian.borrowings.store'));
    };

    return (
        <LibrarianLayout>
            <BorrowingForm
                members={members}
                books={books}
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                submit={submit}
                backRoute="librarian.borrowings.index"
            />
        </LibrarianLayout>
    );
}
