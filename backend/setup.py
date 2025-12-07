from setuptools import setup, find_packages

setup(
    name='kolosal_chat_backend',
    version='0.1',
    packages=find_packages(),
    install_requires=[
        'Flask',
        'requests'
    ],
)