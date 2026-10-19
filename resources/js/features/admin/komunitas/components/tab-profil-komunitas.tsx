import { Label } from '@/components/ui/label';
import { RichTextEditor } from '@/components/rich-text-editor';
import { Text } from '@/components/text';
import type { CommunityFormInstance } from './community-form-instance';
import { CommunityFormPanel } from './community-form-panel';

type Props = {
    form: CommunityFormInstance;
    labels: Record<string, string>;
};

/**
 * The Profil Komunitas tab: vision and mission, the two statements the public
 * detail page prints under the community's description.
 *
 * Both are rich text (D-60), which is why they do not go through Field. Field
 * renders a Label bound to `htmlFor`, and the editor's body is a contenteditable
 * div - a label pointing at that gives a screen reader nothing to attach to,
 * while the editor already carries its own accessible name through `label`. The
 * error sits next to it for the same reason: InputError was the only other thing
 * Field would have provided here.
 *
 * The server sanitizes both on the way in (CommunityValidationRules) and the
 * public page renders them through components/rich-text.tsx, which is what makes
 * storing markup safe rather than merely convenient.
 *
 * @see docs/DECISIONS.md D-60
 */
export function TabProfilKomunitas({ form, labels }: Props) {
    return (
        <CommunityFormPanel
            title="Profil Komunitas"
            description="Lengkapi informasi profil komunitas ini, termasuk visi dan misi. Informasi ini akan ditampilkan di halaman publik komunitas."
        >
            <div className="flex flex-col gap-4">
                <div className="space-y-2">
                    <Label htmlFor="community-vision">
                        {labels.vision ?? 'Visi'}
                    </Label>
                    <RichTextEditor
                        id="community-vision"
                        value={form.data.vision}
                        onChange={(html) => form.setData('vision', html)}
                        label={labels.vision ?? 'Visi'}
                    />
                    {form.errors.vision ? (
                        <Text as="p" variant="body-sm" color="danger">
                            {form.errors.vision}
                        </Text>
                    ) : null}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="community-mission">
                        {labels.mission ?? 'Misi'}
                    </Label>
                    <RichTextEditor
                        id="community-mission"
                        value={form.data.mission}
                        onChange={(html) => form.setData('mission', html)}
                        label={labels.mission ?? 'Misi'}
                    />
                    {form.errors.mission ? (
                        <Text as="p" variant="body-sm" color="danger">
                            {form.errors.mission}
                        </Text>
                    ) : null}
                </div>
            </div>
        </CommunityFormPanel>
    );
}
