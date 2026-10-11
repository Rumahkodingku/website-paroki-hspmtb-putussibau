import { useEffect, useState } from 'react';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Quote,
    Redo2,
    RemoveFormatting,
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

/*
 * Rich text editor, Phase 01 section 23 (Workstream 18).
 *
 * This is a foundation, not an article editor. PRD D-14 chose Tiptap and the
 * roadmap says paragraph, heading, bold, italic, underline, lists, blockquote
 * and link, so those are what is here. Image and table are deliberately absent:
 * image needs the media picker that P10 built as a pipeline but never gave a
 * user interface, and a table button that inserts a table the sanitizer will
 * strip is worse than no button at all. Phase 01 section 23 asks for those two
 * "bila dibutuhkan", and they are not needed yet.
 *
 * Two configurations matter more than the toolbar:
 *
 * Heading levels are 2 to 4 because config/html.php allows h2, h3 and h4 and no
 * h1. h1 is the page title and belongs to the layout. Leaving level 1 on would
 * offer a button whose entire output the server deletes or rewrites, which is a
 * confusing thing to hand an administrator.
 *
 * code, codeBlock, strike and horizontalRule are switched off for the same
 * reason from the other direction: StarterKit enables all four by default and
 * none of them is on the allowlist, so an editor that could produce them would
 * be producing content the server silently destroys.
 *
 * The server is the boundary regardless. Anything posted here is sanitized by
 * UpdateSettingsRequest before it is stored, because the editor being in the
 * browser is not a guarantee about the request body. This component is a
 * convenience, not a control.
 *
 * @see docs/DECISIONS.md D-25
 */

type RichTextEditorProps = {
    /** Sanitized HTML as stored. */
    value: string;
    onChange: (html: string) => void;
    id?: string;
    disabled?: boolean;
    /**
     * Names the toolbar for assistive technology. Unset, it reads "Alat
     * pemformat teks"; pass the field label when the form around it already
     * provides one.
     */
    label?: string;
};

type ToolbarButtonProps = {
    label: string;
    icon: React.ComponentType<{ className?: string }>;
    onClick: () => void;
    active?: boolean;
    disabled?: boolean;
};

/**
 * One toolbar control.
 *
 * aria-pressed rather than a visual style alone: a screen reader announces the
 * state of a toggle button, and a toolbar where bold looks identical whether or
 * not the current selection is bold is unusable without sight.
 */
function ToolbarButton({
    label,
    icon: Icon,
    onClick,
    active = false,
    disabled = false,
}: ToolbarButtonProps) {
    return (
        <Button
            type="button"
            variant="ghost"
            size="icon"
            aria-label={label}
            aria-pressed={active}
            disabled={disabled}
            onClick={onClick}
            className={active ? 'bg-accent text-accent-foreground' : undefined}
        >
            <Icon />
        </Button>
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

    const editor = useEditor({
        // Renders on the server first so the sanitized HTML is already in the
        // document when it hydrates. That avoids a flash of an empty editor on
        // every page that has one.
        immediatelyRender: false,
        editable: !disabled,
        content: value,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3, 4] },
                // Not on the allowlist; see the note at the top of this file.
                code: false,
                codeBlock: false,
                strike: false,
                horizontalRule: false,
                link: {
                    openOnClick: false,
                    // The server forces rel="noopener noreferrer" on every link,
                    // but a new tab opened from the editor preview should behave
                    // the same way.
                    HTMLAttributes: { rel: 'noopener noreferrer' },
                },
            }),
        ],
        onUpdate: ({ editor: current }) => {
            onChange(current.getHTML());
        },
    });

    // The form owns the value, so a reset() or a server round trip has to be
    // able to push a new value into the editor. Comparing against getHTML()
    // rather than the incoming prop is what stops this from resetting the
    // cursor on every keystroke: getHTML() is what onChange last emitted.
    useEffect(() => {
        if (editor && editor.getHTML() !== value) {
            editor.commands.setContent(value, { emitUpdate: false });
        }
    }, [editor, value]);

    useEffect(() => {
        editor?.setEditable(!disabled);
    }, [editor, disabled]);

    const currentHref = editor?.getAttributes('link').href as
        | string
        | undefined;

    const openLinkDialog = () => {
        setLinkValue(currentHref ?? '');
        setLinkDialogOpen(true);
    };

    const applyLink = (href: string) => {
        const trimmed = href.trim();

        // An empty value is how a link is removed here. The alternative is a
        // separate dialog mode for removing, which is a button that already
        // exists below.
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

    if (!editor) {
        return <div className="min-h-40" aria-busy="true" />;
    }

    return (
        <div className="grid gap-2">
            <div
                role="toolbar"
                aria-label={label ?? 'Alat pemformat teks'}
                className="flex flex-wrap items-center gap-1 rounded-t-md border border-input bg-muted/40 p-1"
            >
                <ToolbarButton
                    label="Tebal"
                    icon={Bold}
                    active={editor.isActive('bold')}
                    disabled={disabled}
                    onClick={() => editor.chain().focus().toggleBold().run()}
                />
                <ToolbarButton
                    label="Miring"
                    icon={Italic}
                    active={editor.isActive('italic')}
                    disabled={disabled}
                    onClick={() => editor.chain().focus().toggleItalic().run()}
                />
                <ToolbarButton
                    label="Garis bawah"
                    icon={UnderlineIcon}
                    active={editor.isActive('underline')}
                    disabled={disabled}
                    onClick={() =>
                        editor.chain().focus().toggleUnderline().run()
                    }
                />

                <span aria-hidden="true" className="mx-1 h-6 w-px bg-border" />

                <ToolbarButton
                    label="Subjudul 2"
                    icon={() => <span className="text-xs font-bold">H2</span>}
                    active={editor.isActive('heading', { level: 2 })}
                    disabled={disabled}
                    onClick={() =>
                        editor.chain().focus().toggleHeading({ level: 2 }).run()
                    }
                />
                <ToolbarButton
                    label="Subjudul 3"
                    icon={() => <span className="text-xs font-bold">H3</span>}
                    active={editor.isActive('heading', { level: 3 })}
                    disabled={disabled}
                    onClick={() =>
                        editor.chain().focus().toggleHeading({ level: 3 }).run()
                    }
                />
                <ToolbarButton
                    label="Paragraf biasa"
                    icon={() => <span className="text-xs">P</span>}
                    active={editor.isActive('paragraph')}
                    disabled={disabled}
                    onClick={() => editor.chain().focus().setParagraph().run()}
                />

                <span aria-hidden="true" className="mx-1 h-6 w-px bg-border" />

                <ToolbarButton
                    label="Daftar berbutir"
                    icon={List}
                    active={editor.isActive('bulletList')}
                    disabled={disabled}
                    onClick={() =>
                        editor.chain().focus().toggleBulletList().run()
                    }
                />
                <ToolbarButton
                    label="Daftar bernomor"
                    icon={ListOrdered}
                    active={editor.isActive('orderedList')}
                    disabled={disabled}
                    onClick={() =>
                        editor.chain().focus().toggleOrderedList().run()
                    }
                />
                <ToolbarButton
                    label="Kutipan"
                    icon={Quote}
                    active={editor.isActive('blockquote')}
                    disabled={disabled}
                    onClick={() =>
                        editor.chain().focus().toggleBlockquote().run()
                    }
                />

                <span aria-hidden="true" className="mx-1 h-6 w-px bg-border" />

                <ToolbarButton
                    label="Sisipkan tautan"
                    icon={LinkIcon}
                    active={editor.isActive('link')}
                    disabled={disabled}
                    onClick={openLinkDialog}
                />
                {editor.isActive('link') ? (
                    <ToolbarButton
                        label="Hapus tautan"
                        icon={Unlink}
                        disabled={disabled}
                        onClick={() => editor.chain().focus().unsetLink().run()}
                    />
                ) : null}
                <ToolbarButton
                    label="Hapus format"
                    icon={RemoveFormatting}
                    disabled={disabled}
                    onClick={() => editor.chain().focus().unsetAllMarks().run()}
                />

                <span aria-hidden="true" className="mx-1 h-6 w-px bg-border" />

                <ToolbarButton
                    label="Urungkan"
                    icon={Undo2}
                    disabled={disabled || !editor.can().undo()}
                    onClick={() => editor.chain().focus().undo().run()}
                />
                <ToolbarButton
                    label="Ulangi"
                    icon={Redo2}
                    disabled={disabled || !editor.can().redo()}
                    onClick={() => editor.chain().focus().redo().run()}
                />
            </div>

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
                            onChange={(event) =>
                                setLinkValue(event.target.value)
                            }
                            onKeyDown={(event) => {
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
        </div>
    );
}

export default RichTextEditor;
