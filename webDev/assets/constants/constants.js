class ConstantsLoader {
    static CONSTANTS_FILE_PATH = '/assets/constants/constants.json';

    static data = null;

    static async loadJson(){
        if (this.data === null){
            const response = await fetch(this.CONSTANTS_FILE_PATH);
            const rawData = await response.json();
            
            if (!rawData){
                throw new Error('Error decoding configuration file.');
            }
            this.data = rawData;
        }
    }

    static async get(key){
        await this.loadJson();

        const keys = key.split('.');
        let value = this.data;

        for (const keyPart of keys){
            if (!(keyPart in value)){
                throw new Error(`Config key not found: ${key}`);
            }
            value = value[keyPart];
        }

        return value;
    }

    static async getAdminSchool(){
        await this.loadJson();
        return this.data.adminSchool;
    }

    static async getApiAjax(){
        await this.loadJson();
        return this.data.api.ajaxApi;
    }
}

// define and export the consts (for now just adminSchool)
const consts = (async () => {
    const adminSchool = await ConstantsLoader.getAdminSchool();
    const apiAjax = await ConstantsLoader.getApiAjax();
    return {
        adminSchool,
        apiAjax
    };
})();

// export the consts
export { consts };
export default consts;