import { createStore, applyMiddleware } from "redux";
import { RootReducer, initialState } from './RootReducer';
import { thunk as thunkMiddleware } from 'redux-thunk';

export const ConfigureStore = () => {
  const store = createStore(
    RootReducer,
    initialState,
    applyMiddleware(thunkMiddleware)
  );

  return store;
};

export default ConfigureStore;