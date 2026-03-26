//<script type="text/javascript">
// One Piece Gaiden - Base de datos de cartas
(function() {
    'use strict';
    
    // Configuración del juego
    window.CardGameConfig = {
        specialCardChance: 0.005,       // 0.5% de cartas especiales (muy raras)
        cardsPerPack: 5,
        cardBackImage: 'https://i.imgur.com/baUibTp.png',
        
        // Nuevas configuraciones de rareza
        rarityWeights: {
            1: 50,  // 1 estrella: muy común
            2: 25,  // 2 estrellas: común
            3: 15,  // 3 estrellas: poco común
            4: 6,   // 4 estrellas: rara
            5: 2    // 5 estrellas: épica/legendaria
        },
        
        // Probabilidades especiales
        highRarityChance: 0.3,          // 30% de probabilidad de carta 3+ estrellas
        guaranteedRareEveryPacks: 3     // Garantizar carta rara cada 3 sobres
    };
    
    // Base de datos de packs y cartas
    window.CardGameData = {
        'pack_pretimeskip': {
            id: 'pack_pretimeskip',
            name: 'Deck 1 Pre Time Skip',
            price: 1000,
            image: 'https://i.imgur.com/frcHKyL.png',
            description: 'Deck Pre Time Skip',
            cards: {
                '1': {
                    id: '1',
                    name: 'Gol D. Roger',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/ab/3b/da/ab3bdab72ae1c80a24058602b1eafe5f.jpg',
                    description: 'El futuro Rey de los Piratas.'
                },
                '2': {
                    id: '2',
                    name: 'Sombrero de paja',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/72/a5/ba/72a5ba0c079b8379511935ff2f1d4e80.jpg',
                    description: 'El espadachín de los tres estilos.'
                },
                '3': {
                    id: '3',
                    name: 'No paso nada',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/50/a9/8b/50a98b629b3a201a2d6e0758b89f74af.jpg',
                    description: 'La navegante del clima.'
                },
                '4': {
                    id: '4',
                    name: 'Ace',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/b2/21/c2/b221c2a807d655bb959c116f13e766aa.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '5': {
                    id: '5',
                    name: 'Muerte en Marineford',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/74/43/c1/7443c172fb5b502abe9292f83a662c48.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '6': {
                    id: '6',
                    name: 'Surf en tabla',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/f4/ea/15/f4ea15d72fd3632f3e9e71d8d22317f1.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '7': {
                    id: '7',
                    name: 'Mugiwaras',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/92/1b/fb/921bfb9addf8c59e96a57afe6bfc2c31.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '8': {
                    id: '8',
                    name: 'Nieve',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/ac/9a/82/ac9a8284d7d8b6583dc83fed010eefa5.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '9': {
                    id: '9',
                    name: 'Luffy vs Lucci',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/e1/a7/77/e1a77713f9592df7d9064f4782b9e12d.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '10': {
                    id: '10',
                    name: 'Zoro Alabasta',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/fb/ad/66/fbad66d0fee577016ca1045cef4f587c.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '11': {
                    id: '11',
                    name: 'Bon Clay',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/37/db/f1/37dbf1ac045a89a80c6b1d1414de4702.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '12': {
                    id: '12',
                    name: 'Nico Robin con su madre',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/e6/f8/7a/e6f87ababf79004f26f616bf9b6a96b3.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '13': {
                    id: '13',
                    name: 'Enies Lobby',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/3b/07/b3/3b07b3ae7f90081ffab80c9eaca9ab47.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '14': {
                    id: '14',
                    name: 'Aguila',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/8b/a5/b3/8ba5b30955539c69b26805a33bf7efe2.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '15': {
                    id: '15',
                    name: 'Posando frente a Enies Lobby',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/0a/03/26/0a0326ddaf28af9bdfb37655959a50d4.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '16': {
                    id: '16',
                    name: '3d2y',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/0f/cf/9d/0fcf9dd96160371dff1bf8c392198905.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '17': {
                    id: '17',
                    name: 'Zoro en tren',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/b2/86/18/b286188515cbfe139aef2f0e327ce8ea.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '18': {
                    id: '18',
                    name: 'Franky House',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/a1/1b/82/a11b82f9fb8b7099ee366b7165df48c5.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '19': {
                    id: '19',
                    name: 'Barique Works',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/82/42/fd/8242fdcfb85c67356fc53fa886c6e5db.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '20': {
                    id: '20',
                    name: 'Sogeking',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/09/55/3a/09553a65866886465498a6ab700a871d.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '21': {
                    id: '21',
                    name: 'Shirohige en Marineford',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/82/c8/f5/82c8f50109ac6777d74eb6619f3bfb8e.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '22': {
                    id: '22',
                    name: 'Piratas del Pelirrojo',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/65/f9/9a/65f99a8664c56111986fa77ab0bb3ab0.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '23': {
                    id: '23',
                    name: 'Gia Sekando',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.imgur.com/I6YKirH.png',
                    description: 'La forma más poderosa de Luffy.'
                },
                '24': {
                    id: '24',
                    name: 'Durmiendo',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/0c/24/31/0c243197c638ad4b6b308eaf35710844.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '25': {
                    id: '25',
                    name: 'Shanks salva Luffy',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/12/25/29/12252935591fa0c221807f75bec325dd.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '26': {
                    id: '26',
                    name: 'Muerte de Ace',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/02/43/0b/02430b8fed234a12d7ac6b4d80575dcc.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '27': {
                    id: '27',
                    name: 'Ace Aura',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/25/7a/c9/257ac93b51d2aa06c8662e3b547ee5fe.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '28': {
                    id: '28',
                    name: 'Ace Marineford',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/5b/ce/2d/5bce2d0b681ae44bb34132b1eebf9a98.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '29': {
                    id: '29',
                    name: 'Comenzando la historia',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/b0/b6/08/b0b608392a56e5d52c1d1130e7097365.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '30': {
                    id: '30',
                    name: 'Tony Tony Chopper',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/09/59/fc/0959fc0cacc90a8a9a33e2d145a57c01.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '31': {
                    id: '31',
                    name: 'Akainu',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/d2/19/35/d2193586ca022ba4aecebdf6a35b533a.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '32': {
                    id: '32',
                    name: 'Sengoku',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/82/85/69/828569dc3c3b2b5db2c3e48fcdc8f5da.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '33': {
                    id: '33',
                    name: 'Enel',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/fd/21/38/fd2138b243a220e206071fb1a5bb56b6.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '34': {
                    id: '34',
                    name: 'Akainu golpeado',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/cb/23/ea/cb23eade53389d076b2aea90839d6906.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '35': {
                    id: '35',
                    name: 'Aokiji Almirante',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/df/99/bf/df99bfb880d7f575913e441d72be2cd6.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '36': {
                    id: '36',
                    name: 'Dorry y Brogy',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/a2/b8/08/a2b808efe29982264bcff22a3fc74a94.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '37': {
                    id: '37',
                    name: 'Sanji',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/c1/46/ff/c146ffb22fcc5ca64119cb0e53fa69cc.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '38': {
                    id: '38',
                    name: 'Moby Dick',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/99/b6/c2/99b6c201a715a2672d3eab196f90b105.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '39': {
                    id: '39',
                    name: 'Kizaru',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/b2/f8/41/b2f841f4af16d49c6570c6e746d4929a.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '40': {
                    id: '40',
                    name: 'Nakamas',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/4a/43/42/4a4342b849d37edf51bb20f6873866a0.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '41': {
                    id: '41',
                    name: 'Bon Clay Impel Down',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/80/6d/6b/806d6bf93a40a5be72273ef57b29c27a.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '42': {
                    id: '42',
                    name: 'Shirohige',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/61/53/a1/6153a18ae0172633b12e0ab4f3376e3b.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '43': {
                    id: '43',
                    name: 'Kurohige',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/f8/97/fd/f897fd5ea61dc0a7a5e718b6c62a4bbc.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '44': {
                    id: '44',
                    name: 'Shirohige Ataca',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/8a/06/a9/8a06a9f9b8a0d3095e1a7f8eccf8c0cd.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '45': {
                    id: '45',
                    name: 'Ussop defendiendo a Zoro',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/1d/ec/0b/1dec0bd7fada7bd30c3cde5b6d52a36f.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '46': {
                    id: '46',
                    name: 'Mihawk ataca a Zoro',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/ce/44/c7/ce44c743428536cc8a81b8c604d82774.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '47': {
                    id: '47',
                    name: 'Muerte de Ace',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/41/f1/13/41f1134783413ea8483629211c7ddd4f.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '48': {
                    id: '48',
                    name: 'Luffy comio la Gomu Gomu no Mi',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/2c/12/aa/2c12aa5ea28994209c794147dbdb712f.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '49': {
                    id: '49',
                    name: 'Luffy vs Lucci',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/d4/60/24/d46024e605e981ed04b4032d9360a454.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '50': {
                    id: '50',
                    name: 'Enel',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/21/05/99/210599a011619abf434ea7779a0f18a6.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '51': {
                    id: '51',
                    name: 'Skypiea',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/14/bc/09/14bc091374f824242c32903d6d5ee940.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '52': {
                    id: '52',
                    name: '¿Nika?',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/1c/cb/49/1ccb498d86a0552571098459296a373e.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '53': {
                    id: '53',
                    name: 'Mugiwaras Arlong Park',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/1200x/0a/42/ae/0a42ae91a255b4b675c3d3fad67ea183.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '54': {
                    id: '54',
                    name: 'Enel Luna',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/be/28/0c/be280c2e5d19c81e314060943b5a0d46.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '54': {
                    id: '54',
                    name: 'Kurohige',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/d4/c4/b2/d4c4b24137800b303b176ac990ee0a5f.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '55': {
                    id: '55',
                    name: 'Shanks',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/d0/2b/11/d02b1165fc9e2c902a8b183bb135c754.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '56': {
                    id: '56',
                    name: 'Arlong y Luffy',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/e4/7f/65/e47f650f3fab9d13f4fea0fb1b9aa68b.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '57': {
                    id: '57',
                    name: 'Ace desierto',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/1200x/3b/d2/5a/3bd25a25cf21a180f5b32e020a0c3966.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '58': {
                    id: '58',
                    name: 'Sashiburidana',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/40/d4/c1/40d4c1bf695b72954f744e2effcd55af.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '59': {
                    id: '59',
                    name: 'Zoro 2 espadas',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/c4/d0/04/c4d004978e10e6344479d69acbc73d5d.jpg',
                    description: 'La forma más poderosa de Luffy.'
                },
                '60': {
                    id: '60',
                    name: 'Sanji',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/79/b7/10/79b710554dc165926617df150951f3f7.jpg',
                    description: 'La forma más poderosa de Luffy.'
                }
            }
        },
        'pack_posttimeskip': {
            id: 'pack_posttimeskip',
            name: 'Deck 2 Post Time Skip',
            price: 2500,
            image: 'https://i.imgur.com/cij88vj.png',
            description: 'Post Time Skip',
            cards: {
                '201': {
                    id: '201',
                    name: 'Ace Wano',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/71/5e/12/715e121a26fd026a4a06548484b615c0.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '202': {
                    id: '202',
                    name: 'Garp',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/e5/be/1a/e5be1a68a1c03b16e969a6cc1e16aa65.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '203': {
                    id: '203',
                    name: 'Garp vs Aokiji',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/65/6b/11/656b1186192e6c2112a33c47cf784550.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '204': {
                    id: '204',
                    name: 'Shanks',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/08/f6/c0/08f6c04ad3e6d3cdba130f5ec792f7bd.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '205': {
                    id: '205',
                    name: 'Zoro',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/01/91/92/01919296038c3253ce7ebd36d6d13e85.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '206': {
                    id: '206',
                    name: 'Luffy vs Doflamingo',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/4a/e1/37/4ae1375a32e92e6e51bea26514947f14.jpg',
                    description: 'El emperador pelirrojo.'
                },
                '207': {
                    id: '207',
                    name: 'Yamato',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/fa/77/6b/fa776bce8d4b73f9d4881fffe481fdea.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '208': {
                    id: '208',
                    name: 'Zoro',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/11/5f/dc/115fdc4496338c787c9fe326568a0459.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '209': {
                    id: '209',
                    name: 'Zoro vs Lucci',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/a4/85/56/a48556dc107fefd673da77a06f05d5e0.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '210': {
                    id: '210',
                    name: 'Cross Guild',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/21/e7/6d/21e76d1380e7b32bf43c186858e215eb.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '211': {
                    id: '211',
                    name: 'Momonosuke Dragón',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/7d/1b/97/7d1b97617965529f178ded25832f0572.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '212': {
                    id: '212',
                    name: 'Onigashima',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/e0/e9/76/e0e9769467a9dba3d6263dedd88bb33c.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '213': {
                    id: '213',
                    name: 'Karasu',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/56/3d/0e/563d0eb3670aa0a1eeb5fa3aec4bd066.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '214': {
                    id: '214',
                    name: 'Bichos Egghead',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/c8/85/a6/c885a6da6abb0403252f003ae2af799f.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '215': {
                    id: '215',
                    name: 'Nami Egghead',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/b6/c2/c4/b6c2c4ac6d9d4c82a5c1fe7463b97ee0.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '216': {
                    id: '216',
                    name: 'Yamato ataca',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/75/a1/d7/75a1d72aca2147582b1a8f0bd7dfb9b8.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '217': {
                    id: '217',
                    name: 'Black Maria',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/8a/62/83/8a62832532472632227cc7444a21af27.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '218': {
                    id: '218',
                    name: 'Shirahoshi',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/ad/d7/4d/add74d3ac9b4d2fde97621aad95067b9.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '219': {
                    id: '219',
                    name: 'Boa Hancock',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/cd/49/e2/cd49e209805c413b29cd5af17463b66c.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '220': {
                    id: '220',
                    name: 'Zoro vs Lucci',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/7b/28/99/7b2899edb7dd679f2db128e72880349b.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '221': {
                    id: '221',
                    name: 'Lucci vs Gear 5',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/20/8f/6c/208f6c25b052819d8f066f2e42091591.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '222': {
                    id: '222',
                    name: 'Sabo',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/80/df/80/80df800d60d80d939aa409b9cbd3ce61.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '223': {
                    id: '223',
                    name: 'Kizaru',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/7e/f6/54/7ef6542115bb8933df2fb7220320a95a.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '224': {
                    id: '224',
                    name: 'Snakeman',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/ed/cc/6f/edcc6fc80dfc2a7faa0e0b1dbc2526d9.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '225': {
                    id: '225',
                    name: 'Aokiji',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/35/4b/a0/354ba0127633210f44d514052f5ebeba.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '226': {
                    id: '226',
                    name: 'Trafalgar Law',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/4b/c2/3c/4bc23cb7581973143bb8b4e5f4f8f4eb.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '227': {
                    id: '227',
                    name: 'Gear 5',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/b2/61/2c/b2612c990331ca52ad88500fbf61405f.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '228': {
                    id: '228',
                    name: 'Opening Gear 5',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/56/ff/5b/56ff5bd54bfd2cfa13388b3ccab64e4e.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '229': {
                    id: '229',
                    name: 'Marco y Nekomamushi',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/1a/22/5d/1a225dc4ef83cfd3d340765d4ff657af.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '230': {
                    id: '230',
                    name: 'Rayleigh y Roger',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/07/58/8c/07588ce3cba694f4817a2b0c30e15a98.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '231': {
                    id: '231',
                    name: 'Doflamingo vs Corazon',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/78/dc/d9/78dcd97c3f1cad834dd8f5fcd1d34cfa.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '232': {
                    id: '232',
                    name: 'Llegada a Egghead',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/a5/c5/b2/a5c5b22259bb708d83239794a31dd64c.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '233': {
                    id: '233',
                    name: 'Garp ataca',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/e3/a5/0d/e3a50d17a37331368206de6dfab1b7b5.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '234': {
                    id: '234',
                    name: 'Sanji ataca',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/a6/75/a7/a675a760280858ccfd0287453022dc9b.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '235': {
                    id: '235',
                    name: 'Flash',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/9e/01/8f/9e018f6100033ec051fec6da02ce87d3.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '236': {
                    id: '236',
                    name: 'Sanji lluvia',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/8d/ff/4b/8dff4bf10fe88acc896c3e49b413270c.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '237': {
                    id: '237',
                    name: 'Luffy Egghead',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/4d/e3/51/4de35187f3f159dcc571e0fe0ec1c571.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '238': {
                    id: '238',
                    name: 'Garp joven',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/ff/a5/58/ffa558f3f9f7c4dbd928cad1893f3224.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '239': {
                    id: '239',
                    name: 'Recuerdo de Roger',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/26/2c/cb/262ccb06a7fad49fc244b9e654a39615.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '240': {
                    id: '240',
                    name: 'Luffy en Egghead',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/bc/0f/88/bc0f88f2036d188b65550ae4e37c2428.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '241': {
                    id: '241',
                    name: 'Nico Robin',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/b0/ca/14/b0ca143e0f827b7756af947d2d350c3a.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '242': {
                    id: '242',
                    name: 'Kuma de pequeño',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/32/4a/4f/324a4f314aa22a1de99cf2460113d595.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '243': {
                    id: '243',
                    name: 'Perona en TimeSkip',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/48/cd/08/48cd08fa00ab824b691a1412f9ceca03.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '244': {
                    id: '244',
                    name: 'Portada',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/45/e6/08/45e608e39a182ef65bc896cb489a5abe.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '245': {
                    id: '245',
                    name: 'Vegapunks',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/d7/b8/f1/d7b8f1b25d7a279f45d1700eb64b9949.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '246': {
                    id: '246',
                    name: 'Kuma y Bonney',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/9f/2c/f6/9f2cf6dfbab44ab13ae5b90227cd0e2f.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '247': {
                    id: '247',
                    name: 'Recuerdo de Joy Boy',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/6f/2f/66/6f2f6634d870bb5bacf269359c8b85f2.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '248': {
                    id: '248',
                    name: 'Lucci atacado',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/61/4b/27/614b2794c179b344250bbd85c253e214.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '249': {
                    id: '249',
                    name: 'Luffy y Chopper Egghead',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/b3/81/2f/b3812f0b014f5926f333f761a034005b.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '250': {
                    id: '250',
                    name: 'Luffy vendado',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/90/42/4f/90424f017abf668c5b5a2c98d87c6845.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '251': {
                    id: '251',
                    name: 'Rayleigh',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/45/d3/79/45d379544c6e600c57f32e78ccc80b9f.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '252': {
                    id: '252',
                    name: 'Shirohige',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/21/30/8f/21308f0c49b7d883b5c516ed23550264.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '253': {
                    id: '253',
                    name: 'Shirohige vs Roger',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/c5/a6/c1/c5a6c19fa077d137e1e118e3e55d0988.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '254': {
                    id: '254',
                    name: 'Roger',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/0c/6c/3d/0c6c3d3e9651a7e3c49755303452c5fb.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '255': {
                    id: '255',
                    name: 'Ryuma',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/5a/33/04/5a33040c84dd033fd475cddf00cc49c2.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '256': {
                    id: '256',
                    name: 'Kurohige',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/ad/68/7e/ad687e10d905b4b379eea664acadfc7c.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '257': {
                    id: '257',
                    name: 'Luffy Egghead',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/1200x/42/ff/6e/42ff6e0d5f43eb37a83e5231b97fefb6.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '258': {
                    id: '258',
                    name: 'Luccy vs Zoro',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/7c/69/8b/7c698bf68daecdc4ae9c59d1b26ec04a.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '259': {
                    id: '259',
                    name: 'Kurohige vs Law',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/1200x/b3/9e/ca/b39eca344d05639cf97505e38881b248.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '260': {
                    id: '260',
                    name: 'Sabo',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/a5/d9/b7/a5d9b7be4d6f87956db6da73ab1cb6a1.jpg',
                    description: 'La forma mítica de dragón.'
                }
            }
        },
        'pack_spoilers': {
            id: 'pack_spoilers',
            name: 'Deck 3 Spoilers',
            price: 2500,
            image: 'https://i.imgur.com/zbKbO0j.png',
            description: 'SPOILERS',
            cards: {
                '301': {
                    id: '301',
                    name: 'Mural',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/6b/56/14/6b5614c1c2956a580997c9acc50c1f17.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '302': {
                    id: '302',
                    name: 'Gorosei',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/8e/06/8d/8e068d504344e67fada2906d2a6fc9d0.jpg',
                    description: 'El emperador pelirrojo.'
                },
                '303': {
                    id: '303',
                    name: 'Loki',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/05/6d/1f/056d1fbdf59c2810514058dcf68ed0f7.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '304': {
                    id: '304',
                    name: 'Luffy Vikingo',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/30/d5/70/30d570441a1ada4dd0a6848211dca5c3.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '305': {
                    id: '305',
                    name: 'Elbaf',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/be/b4/0b/beb40bcd52a4045c443aae31e4119aec.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '306': {
                    id: '306',
                    name: 'Shamrock',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/96/b5/3b/96b53bf2e2328ada2769609cf0648124.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '307': {
                    id: '307',
                    name: 'Gunko',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/c8/3c/3a/c83c3a1a5b81bf0bdd70f5c409e0fcd5.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '308': {
                    id: '308',
                    name: 'Shamerock manga',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/ec/24/64/ec2464775adb0476a0204143c5e5bb16.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '309': {
                    id: '309',
                    name: 'Loki manga',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/9f/32/33/9f3233784c9c106163dbde5228a04d88.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '310': {
                    id: '310',
                    name: 'Killingham manga',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/cf/5e/1c/cf5e1c6a2faa61d40f0a9034fe7e6e8b.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '311': {
                    id: '311',
                    name: 'Kuma vs Saturn manga',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/bd/c5/6a/bdc56a40152b22a7a0d4bd734d2a0243.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '312': {
                    id: '312',
                    name: 'Scopper Gaban',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/f9/ba/a4/f9baa4a24c4f6d665a30775a8568ab37.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '313': {
                    id: '313',
                    name: 'Saturn muerte',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/49/b9/3c/49b93c7cb49da1173c346901d7c73b07.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '314': {
                    id: '314',
                    name: 'Gunko Imu',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.imgur.com/xenr2yI.png',
                    description: 'La forma mítica de dragón.'
                },
                '315': {
                    id: '315',
                    name: 'Portada Loki',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/cd/2f/69/cd2f69bb07fe868112af666881d73f65.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '316': {
                    id: '316',
                    name: 'Sommers',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/29/26/3e/29263e0640829e8dc8921b65cc76c6d9.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '317': {
                    id: '317',
                    name: 'Luffy puente',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/47/98/cf/4798cfd39a1a384b3e613f41a21dd169.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '318': {
                    id: '318',
                    name: 'San Marcus Mars',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/87/f9/12/87f9126455cc18f4c47bdb3b46215e7f.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '319': {
                    id: '319',
                    name: 'Topman Warcury',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/60/a5/c5/60a5c5a1dc544fed5beafffb85bd28cd.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '320': {
                    id: '320',
                    name: 'San Ethanbaron V. Nusjuro',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/9d/36/63/9d3663198350380acb05743d003a907c.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '321': {
                    id: '321',
                    name: 'Muerte',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/3f/0c/30/3f0c3013e696733367ae79a098b0e69e.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '322': {
                    id: '322',
                    name: 'San Jaygarcia Saturn',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/35/d9/4b/35d94b8e9c89e1504765895e7f96f122.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '323': {
                    id: '323',
                    name: 'San Shepherd Ju Peter',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/55/f2/f0/55f2f0700583c1800f2af7d7f05f97e0.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '324': {
                    id: '324',
                    name: 'Llegaron',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/8d/f9/95/8df99511877010d5c5cff9857511d316.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '325': {
                    id: '325',
                    name: 'El reencuentro',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/fc/4d/dd/fc4ddd5619d9fed4d6ce10078ae4b8ed.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '326': {
                    id: '326',
                    name: 'Xebeck',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/1200x/83/94/b7/8394b7b13f0eb3e15d83dba73555731a.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '327': {
                    id: '327',
                    name: 'Imu',
                    type: 'normal',
                    rarity: 4,
                    image: 'https://i.pinimg.com/1200x/54/52/75/5452755c313a4510c069e7523549b77c.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '328': {
                    id: '328',
                    name: 'Xebeck',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/b1/ad/39/b1ad396184fcd13d985e1d41a08e5d9c.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '329': {
                    id: '329',
                    name: 'Garling',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/1200x/66/d5/ed/66d5eda644944ee4445cd988bdf699ff.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '330': {
                    id: '330',
                    name: 'Loki joven',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/44/cc/eb/44cceb67c241c2d21a429aefc1205656.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '331': {
                    id: '331',
                    name: 'Loki bebe',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/98/c0/3c/98c03cf23b78aac93c584084cb8509fe.jpg',
                    description: 'La forma mítica de dragón.'
                }

            }
        },
        'pack_manga': {
            id: 'pack_manga',
            name: 'Deck 4 Manga',
            price: 2500,
            image: 'https://i.imgur.com/1tvtsHU.png',
            description: 'SPOILERS',
            cards: {
                '401': {
                    id: '401',
                    name: 'Shirohige',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/84/fa/79/84fa799474093868aa22c99dc3612b60.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '402': {
                    id: '402',
                    name: 'Sanji give me fire',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/db/ba/13/dbba1350cd3aa92697d7a10ef3e2e1e3.jpg',
                    description: 'El emperador pelirrojo.'
                },
                '403': {
                    id: '403',
                    name: 'Zoro vs King',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/fa/88/4a/fa884a29b531912e6d6647deb1da724b.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '404': {
                    id: '404',
                    name: 'Zoro vs Kaido',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/1f/c5/fe/1fc5fec8da34308a07cde4f70fca91bb.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '405': {
                    id: '405',
                    name: 'Ashura Kaku',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/57/33/49/5733493628a6531960502ce40bcb594c.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '406': {
                    id: '406',
                    name: 'Luffy vs Kaido',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/d1/c6/63/d1c6632e2070947274a87b4b0714806e.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '407': {
                    id: '407',
                    name: 'Luffy vs Kaido 2',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/80/c5/42/80c542766ccc9a2212245b550807948c.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '408': {
                    id: '408',
                    name: 'Sanji Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/72/53/22/725322ed9682658ed587d6cb11e24954.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '409': {
                    id: '409',
                    name: 'Doflamingo',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/15/c6/43/15c6432d212f300148a0018db139f28f.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '410': {
                    id: '410',
                    name: 'Akainu Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/8f/df/77/8fdf77e8c1ed848b0ea8d5978b575fc1.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '411': {
                    id: '411',
                    name: 'Rayleigh',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/2f/6b/19/2f6b1940fcdf7c33d161c49a3726ab6b.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '412': {
                    id: '412',
                    name: 'Big Mom',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/40/72/b3/4072b3a4baae2eb355610e7e96154387.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '413': {
                    id: '413',
                    name: 'Kizaru',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/7a/17/ae/7a17aee90ec4c20137114d8b69334d8b.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '414': {
                    id: '414',
                    name: 'Katakuri',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/12/39/ba/1239baa955948cf0efc5883e4ed88e8a.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '415': {
                    id: '415',
                    name: 'Garp vs Aokiji',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/f0/fa/19/f0fa190b57a1365f2b2a6b1ee4653e1d.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '416': {
                    id: '416',
                    name: 'Rayleigh Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/a7/0a/a3/a70aa342801f4458c819ee97559a26e2.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '417': {
                    id: '417',
                    name: 'Crocodile',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/12/60/8c/12608cf0919804a0a5aecc7997e1542a.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '418': {
                    id: '418',
                    name: 'Zoro y Luffy',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/68/74/8c/68748c69207bb1327f40eacef21ec2bb.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '419': {
                    id: '419',
                    name: 'Luffy Kong Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/f8/54/5c/f8545c04a9ce2ccb6164efcd19e2867b.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '420': {
                    id: '420',
                    name: 'Brook y Luffy',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/78/b5/4f/78b54f93de89e8a792e4a283fdb0eb58.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '421': {
                    id: '421',
                    name: 'Ace vs Kurohige',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/dd/54/2a/dd542a56f457a40834308b1c202c44b5.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '422': {
                    id: '422',
                    name: 'Sabo',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/78/be/c5/78bec5e04047d0b2c5210335c346e771.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '423': {
                    id: '423',
                    name: 'Kizaru Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/61/dd/43/61dd433d396906c936cf6c152f299d93.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '424': {
                    id: '424',
                    name: 'Enel',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/a2/a9/97/a2a997b92bd96d376ebfec6525bd8c98.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '425': {
                    id: '425',
                    name: 'Tripulacion del pelirrojo',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/ec/71/10/ec7110985cb46cbd9f1bbc551a048612.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '426': {
                    id: '426',
                    name: 'Luffy y Zoro Wano',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/24/8f/56/248f5676a39c516967a4e615233dd70f.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '427': {
                    id: '427',
                    name: 'Kaido vs Oden',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/d8/66/d0/d866d0f7f5c9f8414ff1b664158d7dea.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '428': {
                    id: '428',
                    name: 'Robbin y Chopper',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/64/0f/7c/640f7cc3d3b0fa347e2f01580c71c79e.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '429': {
                    id: '429',
                    name: 'Ace y Luffy',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/ee/63/db/ee63db1751dca079a801aaae8a5ea012.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '430': {
                    id: '430',
                    name: 'Duo',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/31/6a/9b/316a9bf81d7894d9a2ee46e803192c31.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '431': {
                    id: '431',
                    name: 'Palillos',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/27/f2/16/27f216157b578e77cb9b3057ba7fd932.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '432': {
                    id: '432',
                    name: 'Vivi Nakama',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/cd/62/a2/cd62a283782435315a24d4ef0852fa36.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '433': {
                    id: '433',
                    name: 'Ace',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/df/8a/2e/df8a2e52f1f2d435243682a1a1be195f.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '434': {
                    id: '434',
                    name: 'Aokiji Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/34/40/d5/3440d56ad44497743a3c9fb801218533.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '435': {
                    id: '435',
                    name: 'Suerte',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/64/b8/90/64b890598df28ebd13c15ae6493f8305.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '436': {
                    id: '436',
                    name: 'Egghead',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/7f/17/57/7f1757165c44b99b20b56ccc3b736936.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '437': {
                    id: '437',
                    name: 'Garp Haki',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/b3/dc/ba/b3dcba479eb39aeba570676789e676f7.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '438': {
                    id: '438',
                    name: 'Garp',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/f2/0f/3e/f20f3ee3de5a95765ccdd29196fbb73b.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '439': {
                    id: '439',
                    name: 'Garp y Luffy',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/c7/89/14/c789143d9f7d5c2c87b701176cc597e2.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '440': {
                    id: '440',
                    name: 'Fujitora Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/ef/a9/b2/efa9b2aa786faca768245d083edf3f19.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '441': {
                    id: '441',
                    name: 'Oden Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/4c/aa/53/4caa53ceeacbb6fe330ab78cea463790.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '442': {
                    id: '442',
                    name: 'Mihawk Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/70/d4/65/70d465e6c7faa837f56edcdab8c1c20b.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '443': {
                    id: '443',
                    name: 'Marco Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/2e/62/6d/2e626dfe8506f4a7ac47bad9f6746fcf.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '444': {
                    id: '444',
                    name: 'Usop Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/83/0b/d1/830bd1ccbc803f67db7aa8a6e57ed6ec.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '445': {
                    id: '445',
                    name: 'Garp 2',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/2a/e0/30/2ae03096ef5c5e4efe4090fd72e79b79.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '446': {
                    id: '446',
                    name: 'Ace navegando',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/a9/aa/4c/a9aa4caf1bd7478a576e4490c11a6182.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '447': {
                    id: '447',
                    name: 'Nico Robin',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/18/cd/1c/18cd1c6272850c8c056c20c4635ee81b.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '448': {
                    id: '448',
                    name: 'Aokiji Iris 2',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/c0/1c/7a/c01c7a253b37c1c3c234871fec49b630.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '449': {
                    id: '449',
                    name: 'Luffy Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/b7/d9/78/b7d9787038c25e50a24c9a5cad4066f8.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '450': {
                    id: '450',
                    name: 'Katakuri',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/87/2c/1d/872c1d77f2325e759926db5134d722da.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '451': {
                    id: '451',
                    name: 'Brook Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/d5/02/37/d5023742c14ee811f67e7a42e12c8f9f.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '452': {
                    id: '452',
                    name: 'Enel Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/87/c7/cc/87c7ccde81c356962c04bf8259cef118.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '453': {
                    id: '453',
                    name: 'Nasujuro Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/b5/4e/68/b54e6830fa5313c58034d7879ff7857d.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '454': {
                    id: '454',
                    name: 'Ben Beckman Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/04/e2/27/04e22754cc7bf03aeb25b5296e9b2da7.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '455': {
                    id: '455',
                    name: 'Garp Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/1200x/9c/b1/cb/9cb1cbb107607ed2244ec9cad625b1f8.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '456': {
                    id: '456',
                    name: 'Akainu portada',
                    type: 'especial',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/91/b7/ee/91b7eeefa33605938e4f2e918ba65d66.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '457': {
                    id: '457',
                    name: 'Garp y Sengoku portada',
                    type: 'especial',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/84/f5/ed/84f5ed32c71074a1e256af9b76602182.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '458': {
                    id: '458',
                    name: 'Crocodile portada',
                    type: 'especial',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/8a/a8/7f/8aa87f7875d2d72c801eaa8d39232c56.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '459': {
                    id: '459',
                    name: 'Lucci portada',
                    type: 'especial',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/e5/38/a1/e538a17e5e8ffc182430e1d36f7c3a84.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '460': {
                    id: '460',
                    name: 'Zoro portada',
                    type: 'especial',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/85/9e/02/859e0250bf6c3dfe00bcf86439610d37.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '461': {
                    id: '461',
                    name: 'Luffy Gear 5 Iris',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/d3/22/43/d322439f416e526f27f7fc039a1dc940.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '462': {
                    id: '462',
                    name: 'Elbaf portada',
                    type: 'especial',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/b2/ea/16/b2ea16ce1b73d3060d6a0b900ba68b9d.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '463': {
                    id: '463',
                    name: 'Egghead portada',
                    type: 'especial',
                    rarity: 4,
                    image: 'https://i.pinimg.com/736x/78/e1/81/78e181c634fe3b2e2cc1cbb0ff807ac2.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '464': {
                    id: '464',
                    name: 'Aokiji',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/98/ea/74/98ea74b33fa704b9823b401b530d97f2.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '465': {
                    id: '465',
                    name: 'Galaxy Impact',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/56/cb/30/56cb308d7e94818dcbc2a51c4a6e0cbc.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '466': {
                    id: '466',
                    name: 'Arlong y Luffy',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/1200x/3d/60/65/3d6065152705e4566b46dcd86a70cd8f.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '467': {
                    id: '467',
                    name: 'Roger',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/1200x/c1/6b/40/c16b408e4d266ad16fbecb0d9c5597f0.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '468': {
                    id: '468',
                    name: 'Sorpresa',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/39/2e/03/392e039586a3633c24d5d2886d9f5f4a.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '469': {
                    id: '469',
                    name: 'Traje',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/cc/9a/95/cc9a9539ed6eb037587377032578e81d.jpg',
                    description: 'La forma mítica de dragón.'
                },
                '470': {
                    id: '470',
                    name: 'Moto',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://i.pinimg.com/736x/f7/e7/0c/f7e70c4a0f86f38d5855a0e52ebfb8de.jpg',
                    description: 'La forma mítica de dragón.'
                }
            }
        },
        'pack_simbolos': {
            id: 'pack_simbolos',
            name: 'Deck 5 Simbolos',
            price: 2500,
            image: 'https://i.imgur.com/84OV80M.png',
            description: 'Simbolos',
            cards: {
                '501': {
                    id: '501',
                    name: 'Law',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/3d/4b/93/3d4b9318d5842c40f03b5dfdcd0336a7.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '502': {
                    id: '502',
                    name: 'ASCE',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/43/00/f9/4300f92bda9e84e051589a70e13a7831.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '503': {
                    id: '503',
                    name: 'Poneglyph',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/93/23/28/9323285c05c00eb9b08b047b28cab159.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '504': {
                    id: '504',
                    name: 'Katanas',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/7e/9c/bc/7e9cbc7bf06d9b9aae783960c4367a73.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '505': {
                    id: '505',
                    name: 'Cigarro',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/5c/9e/5b/5c9e5bec0e769301f52abf0888059e5d.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '506': {
                    id: '506',
                    name: 'V',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/e7/b7/43/e7b743cfceff1b092e3bbce3127dd210.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '507': {
                    id: '507',
                    name: 'Tatuaje',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/68/52/d2/6852d2725d77e71c96f629973cf4851a.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '508': {
                    id: '508',
                    name: 'Bandera petalos',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/3f/05/ce/3f05cede372adac6f361202cc0e9d19b.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '509': {
                    id: '509',
                    name: 'Fleur',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/02/56/43/025643fbe34334da55a3d1c33b257823.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '510': {
                    id: '510',
                    name: 'Sombrero',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/32/75/d3/3275d386d99ada100fec0a3d933c26fd.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '511': {
                    id: '511',
                    name: 'Lucci despierto',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/2b/df/4c/2bdf4ca8414671876c41ee9008f6fa4c.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '512': {
                    id: '512',
                    name: 'Marco',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/a6/3a/1e/a63a1ee68d5adb925652459693736aed.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '513': {
                    id: '513',
                    name: 'Kozuki',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/18/3c/e2/183ce24c860920ce6f84fa9a970ea67c.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '514': {
                    id: '514',
                    name: 'Kurohige',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/9f/a6/62/9fa662b99082023941603933a5acc648.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '515': {
                    id: '515',
                    name: 'Roger',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/10/bd/a7/10bda776fa82a6278041aeaed34e87bc.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '516': {
                    id: '516',
                    name: '3D2Y',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/48/2d/07/482d071cae3635ba84390cd6606bef72.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '517': {
                    id: '517',
                    name: 'All',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/f5/44/af/f544af8855f451b3951959c7b613a97e.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '518': {
                    id: '518',
                    name: 'Ace',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/ba/bd/a5/babda5e67363448b2fd4f0a36a6c6409.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '519': {
                    id: '519',
                    name: 'Ussop',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/48/12/5e/48125e3fc99912b2fd324c865fdf9167.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '520': {
                    id: '520',
                    name: 'Jolly Brook',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/7c/73/f4/7c73f4f6addcdb5890f6ae9044902e97.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '521': {
                    id: '521',
                    name: 'Jolly Sanji',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/9c/9a/16/9c9a166605183830ecb8a05fac84b205.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '522': {
                    id: '522',
                    name: 'Jolly Nami',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/c8/3b/d5/c83bd53bdfd61b48ab61e47bc1d60b96.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '523': {
                    id: '523',
                    name: 'Jolly Ussop',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/7b/43/ec/7b43ecf30f9f9f39884bed2b7d157c67.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '524': {
                    id: '524',
                    name: 'Jolly Sunny',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/3e/13/3e/3e133e79e0baa4210ff7ba57c5d2905d.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '525': {
                    id: '525',
                    name: 'Jolly Franky',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/42/d0/b9/42d0b95f803e8c29174188ac5cfb52a8.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '526': {
                    id: '526',
                    name: 'Jolly Robin',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/5e/02/3a/5e023ae4f44a1dcbc9308ff5fed55df8.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '527': {
                    id: '527',
                    name: 'Jolly Zoro',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/95/83/bd/9583bdb94730a4f3e87a9fa767561635.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '528': {
                    id: '528',
                    name: 'Jolly Chopper',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/79/dd/8a/79dd8a83846e6b815d7b23b660e2f0e0.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '529': {
                    id: '529',
                    name: 'Jolly Jimbe',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/16/4a/d5/164ad51a07ab293175b69d890a4cccf1.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '530': {
                    id: '530',
                    name: 'Jolly Luffy',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/7e/6f/17/7e6f17df55c26efdb59baebbe630c90d.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '531': {
                    id: '531',
                    name: 'To be continued',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/f8/06/8d/f8068dda6a44e1c23a91aa741a451e6d.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '532': {
                    id: '532',
                    name: 'Jolly Shirohige',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/7f/50/f6/7f50f6c3eff2f80d430b89b1e593f802.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '533': {
                    id: '533',
                    name: 'Luffy Wano',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/ed/3a/1e/ed3a1e2f3f5f0f4f5e2e2f4e6b6e6f6d.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '534': {
                    id: '534',
                    name: 'Luffy Dressrosa',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://i.pinimg.com/736x/64/63/48/646348937041082546f154654d280e59.jpg',
                    description: 'La criatura más fuerte del mundo.'
                }
            }
        },
        'pack_sexy': {
            id: 'pack_sexy',
            name: 'Deck 6 Sexy',
            price: 4500,
            image: 'https://i.imgur.com/kUL9I06.png',
            description: 'Sexy',
            cards: {
                '601': {
                    id: '601',
                    name: 'Reiju',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/28/d2/a0/28d2a02d5e661c9c0825404ba9cf4d6d.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '602': {
                    id: '602',
                    name: 'Uta',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/8a/86/c3/8a86c3a83d7b0569cdc386df14f3bfce.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '603': {
                    id: '603',
                    name: 'Rebecca',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/05/96/70/059670bbaf59796a45a41112ed8313a3.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '604': {
                    id: '604',
                    name: 'Boney',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.pinimg.com/736x/48/c5/7d/48c57d3e4140498c07c3149931504f53.jpg',
                    description: 'La criatura más fuerte del mundo.'
                },
                '605': {
                    id: '605',
                    name: 'Boa Hancock',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://i.pinimg.com/736x/20/3e/ea/203eea4d6c7c9e8a701c9610003bcfdf.jpg',
                    description: 'La criatura más fuerte del mundo.'
                }
            }
        },
        'pack_personajes_opg': {
            id: 'pack_personajes_opg',
            name: 'Deck 7 Personajes OPG',
            price: 5000,
            image: 'https://i.imgur.com/MAzmiP3.png',
            description: 'Personajes exclusivos de One Piece Gaiden',
            cards: {
                '701': {
                    id: '701',
                    name: 'Personaje OPG 1',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://live.staticflickr.com/65535/55028831753_627556d1b9_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '702': {
                    id: '702',
                    name: 'Personaje OPG 2',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://live.staticflickr.com/65535/55028976015_d122fd94df_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '703': {
                    id: '703',
                    name: 'Personaje OPG 3',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://live.staticflickr.com/65535/55027732822_ba4fd0b0f5_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '704': {
                    id: '704',
                    name: 'Personaje OPG 4',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://live.staticflickr.com/65535/55028870059_fe1606fc6b_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '705': {
                    id: '705',
                    name: 'Personaje OPG Legendario',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://live.staticflickr.com/65535/55029838720_de0c0cf24a_b.jpg',
                    description: 'Personaje legendario exclusivo de One Piece Gaiden.'
                },
                '706': {
                    id: '706',
                    name: 'Personaje OPG 5',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://live.staticflickr.com/65535/55028838183_c193aa28e8_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '707': {
                    id: '707',
                    name: 'Personaje OPG 6',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://live.staticflickr.com/65535/55027797842_85652e38aa_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '708': {
                    id: '708',
                    name: 'Personaje OPG 7',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://live.staticflickr.com/65535/55028889578_b0893ae8e6_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '709': {
                    id: '709',
                    name: 'Personaje OPG 8',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://live.staticflickr.com/65535/55029002354_08d978ace0_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '710': {
                    id: '710',
                    name: 'Personaje OPG 9',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029483478_1226c60925_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '711': {
                    id: '711',
                    name: 'Personaje OPG 10',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029303646_55ab62bc87_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '712': {
                    id: '712',
                    name: 'Personaje OPG 11',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029633773_d4f3935b37_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '713': {
                    id: '713',
                    name: 'Personaje OPG 12',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029512963_6c9877e8c2_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '714': {
                    id: '714',
                    name: 'Personaje OPG 13',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55028442012_b9fa933c0d_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '715': {
                    id: '715',
                    name: 'Personaje OPG 14',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029528353_3d77b7ef57_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '716': {
                    id: '716',
                    name: 'Personaje OPG 15',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029605769_f0464dd366_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '717': {
                    id: '717',
                    name: 'Personaje OPG 16',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029543638_b5957cda97_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '718': {
                    id: '718',
                    name: 'Personaje OPG 17',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029701135_e136c5dba9_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '719': {
                    id: '719',
                    name: 'Personaje OPG 18',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029555036_6a2c3eb937_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '720': {
                    id: '720',
                    name: 'Personaje OPG 19',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029583748_680c8b0d55_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '721': {
                    id: '721',
                    name: 'Personaje OPG 20',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029743860_7ff3fc044c_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '722': {
                    id: '722',
                    name: 'Personaje OPG 21',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55028519947_9cc78206e0_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '723': {
                    id: '723',
                    name: 'Personaje OPG 22',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55028527637_b779662e5f_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '724': {
                    id: '724',
                    name: 'Personaje OPG 23',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029618233_be1f3c5923_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '725': {
                    id: '725',
                    name: 'Personaje OPG 24',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029643963_8b9ff45a01_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '726': {
                    id: '726',
                    name: 'Personaje OPG 25',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029506296_2a30ecfaf6_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '727': {
                    id: '727',
                    name: 'Personaje OPG 26',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55028615837_817f55baca_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '728': {
                    id: '728',
                    name: 'Personaje OPG 27',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029771519_291c50e486_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '729': {
                    id: '729',
                    name: 'Personaje OPG 28',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55028626552_21476e0631_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '730': {
                    id: '730',
                    name: 'Personaje OPG 29',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029866305_2d4ab2be16_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '731': {
                    id: '731',
                    name: 'Personaje OPG 30',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029717913_de9f529418_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '732': {
                    id: '732',
                    name: 'Personaje OPG 31',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55028642592_8d0d63d2d6_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '733': {
                    id: '733',
                    name: 'Personaje OPG 32',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029543901_f284742da4_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '734': {
                    id: '734',
                    name: 'Personaje OPG 33',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029802284_85f4893370_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '735': {
                    id: '735',
                    name: 'Personaje OPG 34',
                    type: 'normal',
                    rarity: 1,
                    image: 'https://live.staticflickr.com/65535/55029733843_b944560b57_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '736': {
                    id: '736',
                    name: 'Personaje OPG 35',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://live.staticflickr.com/65535/55028669232_d9ec7f71ca_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '737': {
                    id: '737',
                    name: 'Personaje OPG 36',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://live.staticflickr.com/65535/55029825759_9e41508276_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '738': {
                    id: '738',
                    name: 'Personaje OPG 37',
                    type: 'especial',
                    rarity: 4,
                    image: 'https://live.staticflickr.com/65535/55029575136_447e09d617_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '739': {
                    id: '739',
                    name: 'Personaje OPG 38',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://live.staticflickr.com/65535/55029914990_a2c95089bc_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '740': {
                    id: '740',
                    name: 'Personaje OPG 39',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://live.staticflickr.com/65535/55029840044_09356db85c_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '741': {
                    id: '741',
                    name: 'Personaje OPG 40',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://live.staticflickr.com/65535/55029795153_f22f6045d7_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '742': {
                    id: '742',
                    name: 'Personaje OPG 41',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://live.staticflickr.com/65535/55028729942_20481d2a3e_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '743': {
                    id: '743',
                    name: 'Personaje OPG 42',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://live.staticflickr.com/65535/55028734052_fa5dafccbb_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '744': {
                    id: '744',
                    name: 'Personaje OPG 43',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://live.staticflickr.com/65535/55028739417_45af13f849_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '745': {
                    id: '745',
                    name: 'Personaje OPG 44',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://live.staticflickr.com/65535/55030435045_9953040aa8_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '746': {
                    id: '746',
                    name: 'Personaje OPG 45',
                    type: 'normal',
                    rarity: 2,
                    image: 'https://live.staticflickr.com/65535/55030289268_a5053ba686_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '747': {
                    id: '747',
                    name: 'Personaje OPG 46',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://live.staticflickr.com/65535/55030375329_88b05e9e6d_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '748': {
                    id: '748',
                    name: 'Personaje OPG 47',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://live.staticflickr.com/65535/55029229272_9a810139b3_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '749': {
                    id: '749',
                    name: 'Personaje OPG 48',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://live.staticflickr.com/65535/55030470845_e381f91510_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '750': {
                    id: '750',
                    name: 'Personaje OPG 49',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://live.staticflickr.com/65535/55030396169_f9e7ebe436_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                },
                '751': {
                    id: '751',
                    name: 'Personaje OPG 50',
                    type: 'normal',
                    rarity: 3,
                    image: 'https://live.staticflickr.com/65535/55030400569_c2500f43ee_b.jpg',
                    description: 'Personaje exclusivo de One Piece Gaiden.'
                }
            }
        },
        'pack_mundo_opg': {
            id: 'pack_mundo_opg',
            name: 'Deck 8 Mundo OPG',
            price: 5500,
            image: 'https://i.imgur.com/CdLG8cQ.png',
            description: 'Lugares y escenarios del mundo de One Piece Gaiden',
            cards: {
                '801': {
                    id: '801',
                    name: 'Lugar OPG 1',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://live.staticflickr.com/65535/55028838183_c193aa28e8_b.jpg',
                    description: 'Lugar icónico de One Piece Gaiden.'
                },
                '802': {
                    id: '802',
                    name: 'Lugar OPG 2',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://live.staticflickr.com/65535/55028831753_627556d1b9_b.jpg',
                    description: 'Lugar icónico de One Piece Gaiden.'
                },
                '803': {
                    id: '803',
                    name: 'Lugar OPG 3',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.imgur.com/baUibTp.png',
                    description: 'Lugar icónico de One Piece Gaiden.'
                },
                '804': {
                    id: '804',
                    name: 'Lugar OPG 4',
                    type: 'objeto',
                    rarity: 4,
                    image: 'https://i.imgur.com/baUibTp.png',
                    description: 'Lugar icónico de One Piece Gaiden.'
                },
                '805': {
                    id: '805',
                    name: 'Mundo OPG Épico',
                    type: 'especial',
                    rarity: 5,
                    image: 'https://i.imgur.com/baUibTp.png',
                    description: 'Escenario épico del mundo de One Piece Gaiden.'
                }
            }
        }
    };
    
    console.log('🎴 CardGame Data cargado correctamente');
})();
//</script>