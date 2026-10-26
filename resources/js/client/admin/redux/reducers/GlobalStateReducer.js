import Types from "../Types";

export const initialState = {
    
}

export const GlobalReducer = (state = initialState, action) => {
    switch (action.type) {
        case Types.INITIALIZE_GLOBAL_STATE: {
            state = action.payload;
            return state;
        }
        case Types.SET_GLOBAL_STATE: {
            return { 
                ...state, 
                ...action.payload
            };
        }
        case Types.SAVE_API_TOKEN: {
            const {apiToken} = action.payload;
            localStorage.setItem('apiToken', apiToken);

            return { ...state, apiToken };
        }
        case Types.REMOVE_API_TOKEN: {
            localStorage.removeItem("apiToken");

            return { ...state, apiToken: null };
        }
        default:
            return state;
    }
}

export default GlobalReducer;