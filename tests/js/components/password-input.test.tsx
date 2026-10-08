import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vite-plus/test';
import PasswordInput from '@/components/password-input';

/**
 * The field is queried by label rather than by role because an
 * <input type="password"> has no implicit ARIA role at all: password inputs are
 * deliberately kept out of the accessibility tree as textboxes, so
 * getByRole('textbox') can never match one, masked or not.
 *
 * Real screens pair this component with an external <InputLabel>, which is why
 * aria-label is supplied here instead of the component shipping one.
 */
function renderField(className?: string) {
    const view = render(
        <PasswordInput aria-label="Password" className={className} />,
    );

    return {
        field: screen.getByLabelText('Password'),
        input: view.container.querySelector('input'),
    };
}

describe('PasswordInput', () => {
    it('masks the value on first render', () => {
        const { field } = renderField();

        expect(field).toHaveAttribute('type', 'password');
        expect(
            screen.getByRole('button', { name: 'Show password' }),
        ).toBeInTheDocument();
    });

    it('toggles between masked and visible, swapping the button label', async () => {
        const user = userEvent.setup();
        const { field } = renderField();

        await user.click(screen.getByRole('button', { name: 'Show password' }));

        expect(field).toHaveAttribute('type', 'text');
        expect(
            screen.getByRole('button', { name: 'Hide password' }),
        ).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Hide password' }));

        expect(field).toHaveAttribute('type', 'password');
    });

    /**
     * The toggle sits inside the login <form>. A <button> with no type defaults
     * to submit, so without the explicit type the first click would post the
     * form to the server instead of revealing the password.
     */
    it('does not submit the surrounding form when clicked', async () => {
        const user = userEvent.setup();
        let submitted = false;

        render(
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    submitted = true;
                }}
            >
                <PasswordInput aria-label="Password" />
            </form>,
        );

        await user.click(screen.getByRole('button', { name: 'Show password' }));

        expect(submitted).toBe(false);
        expect(screen.getByLabelText('Password')).toHaveAttribute(
            'type',
            'text',
        );
    });

    it('lets a caller padding utility win over the room it reserves for the toggle', () => {
        const { input } = renderField('pr-2');

        // cn() merges rather than concatenating, so the caller's pr-2 replaces
        // the component's own pr-10 instead of both landing on the element.
        expect(input).toHaveClass('pr-2');
        expect(input?.className).not.toContain('pr-10');
    });

    it('keeps a non-conflicting caller className alongside its own', () => {
        const { input } = renderField('w-full');

        // w-full and pr-10 are different properties, so both survive.
        expect(input).toHaveClass('w-full', 'pr-10');
    });
});
