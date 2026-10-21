import { useEffect, useRef, useState } from 'react';
import { EditorContent, useEditor, useEditorState } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Highlight from '@tiptap/extension-highlight';
import Superscript from '@tiptap/extension-superscript';
import Subscript from '@tiptap/extension-subscript';
import TextAlign from '@tiptap/extension-text-align';
import Image from '@tiptap/extension-image';
import { TableKit } from '@tiptap/extension-table';
import {
    AlignCenter,
    AlignJustify,
    AlignLeft,
    AlignRight,
    Bold,
    Code,
    CodeXml,
    Columns3,
    Highlighter,
    ImagePlus,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Minus,
    Quote,
    Redo2,
    RemoveFormatting,
    Rows3,
    Strikethrough,
    Subscript as SubscriptIcon,
    Superscript as SuperscriptIcon,
    Table as TableIcon,
    Trash2,
    Underline as UnderlineIcon,
    Undo2,
    Unlink,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Text } from '@/components/text';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useMediaUpload } from '@/hooks/use-media-upload';

type RichTextEditorProps = {
    /** Sanitized HTML as stored. */
    value: string;
    onChange: (html: string) => void;
    id?: string;
    disabled?: boolean;
    label?: string;
};

type ToolbarButtonProps = {
    label: string;
    icon: React.ComponentType<{ className?: string }>;
    onClick: () => void;
    active?: boolean;
    disabled?: boolean;
};

function ToolbarButton({
    label,
    icon: Icon,
    onClick,
    active = false,
    disabled = false,
}: ToolbarButtonProps) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label={label}
                    aria-pressed={active}
                    disabled={disabled}
                    onClick={onClick}
                    className={
                        active ? 'bg-accent text-accent-foreground' : undefined
                    }
                >
                    <Icon />
                </Button>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}

function ToolbarDivider() {
    return <span aria-hidden="true" className="mx-1 h-6 w-px bg-border" />;
}

function HeadingButton({
    label,
    text,
    onClick,
    active,
    disabled,
}: {
    label: string;
    text: string;
    onClick: () => void;
    active: boolean;
    disabled: boolean;
}) {
    return (
        <ToolbarButton
            label={label}
            icon={() => <span className="text-xs font-bold">{text}</span>}
            active={active}
            disabled={disabled}
            onClick={onClick}
        />
    );
}

export function RichTextEditor({
    value,
    onChange,
    id,
    disabled = false,
    label,
}: RichTextEditorProps) {
    const [linkDialogOpen, setLinkDialogOpen] = useState(false);
    const [linkValue, setLinkValue] = useState('');

    const [imageDialogOpen, setImageDialogOpen] = useState(false);
    const [imageAlt, setImageAlt] = useState('');
    const imageInputRef = useRef<HTMLInputElement>(null);
    const imageUpload = useMediaUpload();

    const editor = useEditor({
        immediatelyRender: false,
        editable: !disabled,
        content: value,
        extensions: [
            StarterKit.configure({
                heading: {
                    levels: [2, 3, 4],
                },

                link: {
                    openOnClick: false,
                    HTMLAttributes: {
                        rel: 'noopener noreferrer',
                    },
                },
            }),
            Highlight.configure({
                multicolor: false,
            }),

            Superscript,
            Subscript,
            TextAlign.configure({
                types: ['heading', 'paragraph', 'tableCell', 'tableHeader'],
            }),

            Image.configure({
                inline: false,
                allowBase64: false,
            }),

            TableKit.configure({
                table: {
                    resizable: false,
                },
            }),
        ],
        onUpdate: ({ editor: current }) => {
            onChange(current.getHTML());
        },
    });

    const state = useEditorState({
        editor,
        selector: ({ editor: current }) => {
            if (current === null) {
                return null;
            }

            const align =
                (['left', 'center', 'right', 'justify'] as const).find(
                    (value) => current.isActive({ textAlign: value }),
                ) ?? null;

            return {
                isBold: current.isActive('bold'),
                isItalic: current.isActive('italic'),
                isUnderline: current.isActive('underline'),
                isStrike: current.isActive('strike'),
                isCode: current.isActive('code'),
                isHighlight: current.isActive('highlight'),
                isSuperscript: current.isActive('superscript'),
                isSubscript: current.isActive('subscript'),
                isParagraph: current.isActive('paragraph'),
                isH2: current.isActive('heading', { level: 2 }),
                isH3: current.isActive('heading', { level: 3 }),
                isH4: current.isActive('heading', { level: 4 }),
                isBulletList: current.isActive('bulletList'),
                isOrderedList: current.isActive('orderedList'),
                isBlockquote: current.isActive('blockquote'),
                isCodeBlock: current.isActive('codeBlock'),
                isLink: current.isActive('link'),
                isInTable: current.isActive('table'),
                align,
                canUndo: current.can().undo(),
                canRedo: current.can().redo(),
                href: current.getAttributes('link').href as string | undefined,
            };
        },
    });

    useEffect(() => {
        if (editor && editor.getHTML() !== value) {
            editor.commands.setContent(value, { emitUpdate: false });
        }
    }, [editor, value]);

    useEffect(() => {
        editor?.setEditable(!disabled);
    }, [editor, disabled]);

    const openLinkDialog = () => {
        setLinkValue(state?.href ?? '');
        setLinkDialogOpen(true);
    };

    const applyLink = (href: string) => {
        const trimmed = href.trim();
        if (trimmed === '') {
            editor?.chain().focus().extendMarkRange('link').unsetLink().run();

            return;
        }

        editor
            ?.chain()
            .focus()
            .extendMarkRange('link')
            .setLink({ href: trimmed })
            .run();
    };

    const openImageDialog = () => {
        imageUpload.reset();
        setImageAlt('');
        setImageDialogOpen(true);
    };

    const handleImageFile = async (file: File) => {
        const media = await imageUpload.upload(file, { altText: imageAlt });

        if (media === null) {
            return;
        }

        const src =
            media.variants.find((variant) => variant.name === 'large')?.url ??
            media.variants.find((variant) => variant.name === 'medium')?.url ??
            media.variants[0]?.url;

        if (src !== undefined && src !== null) {
            editor?.chain().focus().setImage({ src, alt: imageAlt }).run();
        }

        setImageDialogOpen(false);
        imageUpload.reset();
    };

    if (!editor || state === null) {
        return <div className="min-h-40" aria-busy="true" />;
    }

    const setAlign = (align: 'left' | 'center' | 'right' | 'justify') =>
        editor.chain().focus().setTextAlign(align).run();

    return (
        <div className="grid gap-2">
            <TooltipProvider>
                <div
                    role="toolbar"
                    aria-label={label ?? 'Alat pemformat teks'}
                    className="flex flex-wrap items-center gap-1 rounded-t-md border border-input bg-muted/40 p-1"
                >
                    <ToolbarButton
                        label="Tebal"
                        icon={Bold}
                        active={state.isBold}
                        disabled={disabled}
                        onClick={() =>
                            editor.chain().focus().toggleBold().run()
                        }
                    />
                    <ToolbarButton
                        label="Miring"
                        icon={Italic}
                        active={state.isItalic}
                        disabled={disabled}
                        onClick={() =>
                            editor.chain().focus().toggleItalic().run()
                        }
                    />
                    <ToolbarButton
                        label="Garis bawah"
                        icon={UnderlineIcon}
                        active={state.isUnderline}
                        disabled={disabled}
                        onClick={() =>
                            editor.chain().focus().toggleUnderline().run()
                        }
                    />
                    <ToolbarButton
                        label="Coret"
                        icon={Strikethrough}
                        active={state.isStrike}
                        disabled={disabled}
                        onClick={() =>
                            editor.chain().focus().toggleStrike().run()
                        }
                    />
                    <ToolbarButton
                        label="Kode sebaris"
                        icon={Code}
                        active={state.isCode}
                        disabled={disabled}
                        onClick={() =>
                            editor.chain().focus().toggleCode().run()
                        }
                    />
                    <ToolbarButton
                        label="Sorot"
                        icon={Highlighter}
                        active={state.isHighlight}
                        disabled={disabled}
                        onClick={() =>
                            editor.chain().focus().toggleHighlight().run()
                        }
                    />
                    <ToolbarButton
                        label="Superskrip"
                        icon={SuperscriptIcon}
                        active={state.isSuperscript}
                        disabled={disabled}
                        onClick={() =>
                            editor.chain().focus().toggleSuperscript().run()
                        }
                    />
                    <ToolbarButton
                        label="Subskrip"
                        icon={SubscriptIcon}
                        active={state.isSubscript}
                        disabled={disabled}
                        onClick={() =>
                            editor.chain().focus().toggleSubscript().run()
                        }
                    />

                    <ToolbarDivider />

                    <HeadingButton
                        label="Paragraf biasa"
                        text="P"
                        active={state.isParagraph}
                        disabled={disabled}
                        onClick={() =>
                            editor.chain().focus().setParagraph().run()
                        }
                    />
                    <HeadingButton
                        label="Subjudul 2"
                        text="H2"
                        active={state.isH2}
                        disabled={disabled}
                        onClick={() =>
                            editor
                                .chain()
                                .focus()
                                .toggleHeading({ level: 2 })
                                .run()
                        }
                    />
                    <HeadingButton
                        label="Subjudul 3"
                        text="H3"
                        active={state.isH3}
                        disabled={disabled}
                        onClick={() =>
                            editor
                                .chain()
                                .focus()
                                .toggleHeading({ level: 3 })
                                .run()
                        }
                    />
                    <HeadingButton
                        label="Subjudul 4"
                        text="H4"
                        active={state.isH4}
                        disabled={disabled}
                        onClick={() =>
                            editor
                                .chain()
                                .focus()
                                .toggleHeading({ level: 4 })
                                .run()
                        }
                    />

                    <ToolbarDivider />

                    <ToolbarButton
                        label="Rata kiri"
                        icon={AlignLeft}
                        active={state.align === 'left'}
                        disabled={disabled}
                        onClick={() => setAlign('left')}
                    />
                    <ToolbarButton
                        label="Rata tengah"
                        icon={AlignCenter}
                        active={state.align === 'center'}
                        disabled={disabled}
                        onClick={() => setAlign('center')}
                    />
                    <ToolbarButton
                        label="Rata kanan"
                        icon={AlignRight}
                        active={state.align === 'right'}
                        disabled={disabled}
                        onClick={() => setAlign('right')}
                    />
                    <ToolbarButton
                        label="Rata kiri kanan"
                        icon={AlignJustify}
                        active={state.align === 'justify'}
                        disabled={disabled}
                        onClick={() => setAlign('justify')}
                    />

                    <ToolbarDivider />

                    <ToolbarButton
                        label="Daftar berbutir"
                        icon={List}
                        active={state.isBulletList}
                        disabled={disabled}
                        onClick={() =>
                            editor.chain().focus().toggleBulletList().run()
                        }
                    />
                    <ToolbarButton
                        label="Daftar bernomor"
                        icon={ListOrdered}
                        active={state.isOrderedList}
                        disabled={disabled}
                        onClick={() =>
                            editor.chain().focus().toggleOrderedList().run()
                        }
                    />
                    <ToolbarButton
                        label="Kutipan"
                        icon={Quote}
                        active={state.isBlockquote}
                        disabled={disabled}
                        onClick={() =>
                            editor.chain().focus().toggleBlockquote().run()
                        }
                    />
                    <ToolbarButton
                        label="Blok kode"
                        icon={CodeXml}
                        active={state.isCodeBlock}
                        disabled={disabled}
                        onClick={() =>
                            editor.chain().focus().toggleCodeBlock().run()
                        }
                    />
                    <ToolbarButton
                        label="Garis pemisah"
                        icon={Minus}
                        disabled={disabled}
                        onClick={() =>
                            editor.chain().focus().setHorizontalRule().run()
                        }
                    />

                    <ToolbarDivider />

                    <ToolbarButton
                        label="Sisipkan tautan"
                        icon={LinkIcon}
                        active={state.isLink}
                        disabled={disabled}
                        onClick={openLinkDialog}
                    />
                    {state.isLink ? (
                        <ToolbarButton
                            label="Hapus tautan"
                            icon={Unlink}
                            disabled={disabled}
                            onClick={() =>
                                editor.chain().focus().unsetLink().run()
                            }
                        />
                    ) : null}
                    <ToolbarButton
                        label="Sisipkan gambar"
                        icon={ImagePlus}
                        disabled={disabled}
                        onClick={openImageDialog}
                    />

                    <DropdownMenu>
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <DropdownMenuTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Tabel"
                                        disabled={disabled}
                                        className={
                                            state.isInTable
                                                ? 'bg-accent text-accent-foreground'
                                                : undefined
                                        }
                                    >
                                        <TableIcon />
                                    </Button>
                                </DropdownMenuTrigger>
                            </TooltipTrigger>
                            <TooltipContent>Tabel</TooltipContent>
                        </Tooltip>
                        <DropdownMenuContent align="start" className="w-56">
                            <DropdownMenuItem
                                onSelect={() =>
                                    editor
                                        .chain()
                                        .focus()
                                        .insertTable({
                                            rows: 3,
                                            cols: 3,
                                            withHeaderRow: true,
                                        })
                                        .run()
                                }
                            >
                                <TableIcon />
                                Sisipkan tabel
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                disabled={!state.isInTable}
                                onSelect={() =>
                                    editor.chain().focus().addRowAfter().run()
                                }
                            >
                                <Rows3 />
                                Tambah baris
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                disabled={!state.isInTable}
                                onSelect={() =>
                                    editor.chain().focus().deleteRow().run()
                                }
                            >
                                <Rows3 />
                                Hapus baris
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                disabled={!state.isInTable}
                                onSelect={() =>
                                    editor
                                        .chain()
                                        .focus()
                                        .addColumnAfter()
                                        .run()
                                }
                            >
                                <Columns3 />
                                Tambah kolom
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                disabled={!state.isInTable}
                                onSelect={() =>
                                    editor.chain().focus().deleteColumn().run()
                                }
                            >
                                <Columns3 />
                                Hapus kolom
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                disabled={!state.isInTable}
                                onSelect={() =>
                                    editor.chain().focus().deleteTable().run()
                                }
                            >
                                <Trash2 />
                                Hapus tabel
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <ToolbarButton
                        label="Hapus format"
                        icon={RemoveFormatting}
                        disabled={disabled}
                        onClick={() =>
                            editor.chain().focus().unsetAllMarks().run()
                        }
                    />

                    <ToolbarDivider />

                    <ToolbarButton
                        label="Urungkan"
                        icon={Undo2}
                        disabled={disabled || !state.canUndo}
                        onClick={() => editor.chain().focus().undo().run()}
                    />
                    <ToolbarButton
                        label="Ulangi"
                        icon={Redo2}
                        disabled={disabled || !state.canRedo}
                        onClick={() => editor.chain().focus().redo().run()}
                    />
                </div>
            </TooltipProvider>

            <EditorContent
                editor={editor}
                id={id}
                aria-label={label}
                className="rich-text rounded-b-md border border-input px-3 py-2 focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2 focus-within:ring-offset-background"
            />

            <Dialog open={linkDialogOpen} onOpenChange={setLinkDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Sisipkan tautan</DialogTitle>
                        <DialogDescription>
                            Isi alamat tautan, atau kosongkan untuk menghapus
                            tautan yang sudah ada.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor={`${id ?? 'rich-text'}-href`}>
                            Alamat tautan
                        </Label>
                        <Input
                            id={`${id ?? 'rich-text'}-href`}
                            type="url"
                            inputMode="url"
                            placeholder="https://contoh.test atau /halaman"
                            value={linkValue}
                            onChange={(event: any) =>
                                setLinkValue(event.target.value)
                            }
                            onKeyDown={(event: any) => {
                                if (event.key === 'Enter') {
                                    event.preventDefault();
                                    applyLink(linkValue);
                                    setLinkDialogOpen(false);
                                }
                            }}
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setLinkDialogOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            onClick={() => {
                                applyLink(linkValue);
                                setLinkDialogOpen(false);
                            }}
                        >
                            Sisipkan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={imageDialogOpen} onOpenChange={setImageDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Sisipkan gambar</DialogTitle>
                        <DialogDescription>
                            Unggah gambar, lalu gambar akan disisipkan pada
                            posisi kursor setelah diproses.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor={`${id ?? 'rich-text'}-image-alt`}>
                            Teks alternatif
                        </Label>
                        <Input
                            id={`${id ?? 'rich-text'}-image-alt`}
                            type="text"
                            maxLength={255}
                            placeholder="Contoh: Suasana acara di halaman depan Gereja Paroki"
                            value={imageAlt}
                            onChange={(event: any) =>
                                setImageAlt(event.target.value)
                            }
                        />
                    </div>

                    <input
                        ref={imageInputRef}
                        id={`${id ?? 'rich-text'}-image-file`}
                        type="file"
                        accept="image/jpeg,image/png"
                        className="sr-only"
                        onChange={(event: any) => {
                            const file = event.target.files?.[0];

                            if (file) {
                                void handleImageFile(file);
                            }

                            event.target.value = '';
                        }}
                    />

                    <Button
                        type="button"
                        variant="outline"
                        disabled={imageUpload.uploading}
                        onClick={() => imageInputRef.current?.click()}
                    >
                        {imageUpload.uploading ? 'Memproses…' : 'Pilih gambar'}
                    </Button>

                    {imageUpload.uploading ? (
                        <Text
                            as="p"
                            variant="body-sm"
                            color="muted"
                            role="status"
                        >
                            Gambar sedang diproses menjadi beberapa ukuran.
                        </Text>
                    ) : null}

                    {imageUpload.failed ? (
                        <Text
                            as="p"
                            variant="body-sm"
                            color="danger"
                            role="alert"
                        >
                            {imageUpload.failed}
                        </Text>
                    ) : null}

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setImageDialogOpen(false)}
                        >
                            Tutup
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}

export default RichTextEditor;
