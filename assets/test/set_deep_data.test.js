import { setDeepData } from '../src/set_deep_data';

describe('setDeepData', () => {
    it('sets a simple key', () => {
        const data = {
            message: 'original',
            isPublished: true,
        }
        const finalData = setDeepData(data, 'message', 'new_message');
        expect(finalData.message).toEqual('new_message');
        expect(finalData.isPublished).toEqual(true);
        // original is not modified
        expect(data.message).toEqual('original');
    });

    it('sets a deeper key', () => {
        const data = {
            post: {
                message: 'original',
                isPublished: true,
            },
            other_field: 'another field'
        }
        const finalData = setDeepData(data, 'post.message', 'new_message');
        expect(finalData.post.message).toEqual('new_message');
        expect(finalData.post.isPublished).toEqual(true);
        // original is not modified
        expect(data).toEqual({
            post: {
                message: 'original',
                isPublished: true,
            },
            other_field: 'another field'
        });
    });

    it('sets a very deep key', () => {
        const data = {
            post: {
                user: {
                    username: 'weaverryan',
                    favoriteColor: 'pink',
                },
                isPublished: true,
            },
            other_field: 'another field'
        }
        const finalData = setDeepData(data, 'post.user.favoriteColor', 'orange');
        expect(finalData).toEqual({
            post: {
                user: {
                    username: 'weaverryan',
                    favoriteColor: 'orange',
                },
                isPublished: true,
            },
            other_field: 'another field'
        });
        expect(data).toEqual({
            post: {
                user: {
                    username: 'weaverryan',
                    favoriteColor: 'pink',
                },
                isPublished: true,
            },
            other_field: 'another field'
        });
    });

    // sets undefined keys, even recursively
});
